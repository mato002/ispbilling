<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\CommunicationCampaign;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\SmsGroup;
use App\Models\SmsMessageQueue;
use App\Services\CommunicationCampaignService;
use App\Services\CommunicationsAudienceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommunicationCampaignController extends Controller
{
    use InteractsWithListings;

    public function __construct(
        protected CommunicationsAudienceService $audience,
        protected CommunicationCampaignService $campaigns,
    ) {}

    public function index(Request $request): View
    {
        $ispId = Auth::user()->isp_id;
        $query = CommunicationCampaign::forIsp($ispId)
            ->with('createdBy')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $this->resolvePerPage($request, 20);
        $campaigns = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.communications.index'), [
            'status' => [
                'label' => 'Status',
                'values' => [
                    'draft' => 'Draft',
                    'scheduled' => 'Scheduled',
                    'sending' => 'Sending',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                ],
            ],
        ]);

        return view('isp.communications.index', compact('campaigns', 'filterChips'));
    }

    public function create(): View
    {
        $ispId = Auth::user()->isp_id;

        return view('isp.communications.create', [
            'plans' => Plan::forIsp($ispId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'access_type']),
            'routers' => Router::forIsp($ispId)->active()->orderBy('name')->get(['id', 'name']),
            'groups' => SmsGroup::forIsp($ispId)->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::forIsp($ispId)->orderBy('full_name')->limit(200)->get(['id', 'full_name', 'phone_number']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $ispId = Auth::user()->isp_id;
        $validated = $this->validateCampaign($request);

        $filters = $this->buildAudienceFilters($validated);
        $this->assertAudienceOwnership($ispId, $filters);
        $sendAction = $validated['send_action'];

        $campaign = CommunicationCampaign::create([
            'isp_id' => $ispId,
            'created_by_user_id' => Auth::id(),
            'title' => $validated['title'],
            'message_body' => $validated['message_body'],
            'channel' => $validated['channel'],
            'audience_filters' => $filters,
            'status' => CommunicationCampaign::STATUS_DRAFT,
            'scheduled_at' => $sendAction === 'schedule' ? $validated['scheduled_at'] : null,
        ]);

        if ($sendAction === 'draft') {
            return redirect()
                ->route('isp.communications.show', $campaign)
                ->with('success', 'Campaign saved as draft.');
        }

        if ($sendAction === 'schedule') {
            $this->campaigns->schedule($campaign->fresh());

            return redirect()
                ->route('isp.communications.show', $campaign)
                ->with('success', 'Campaign scheduled successfully.');
        }

        $this->campaigns->launch($campaign->fresh());

        return redirect()
            ->route('isp.communications.show', $campaign)
            ->with('success', 'Campaign queued for delivery.');
    }

    public function show(CommunicationCampaign $campaign): View
    {
        $this->authorizeCampaign($campaign);

        $preview = $this->audience->resolve(
            $campaign->isp_id,
            $campaign->audience_filters ?? [],
            $campaign->channel,
        )->take(50);

        $deliveryLogs = SmsMessageQueue::query()
            ->where('campaign_id', $campaign->id)
            ->with('customer')
            ->latest()
            ->limit(100)
            ->get();

        $filterLabels = $this->audience->describeFilters(
            $campaign->isp_id,
            $campaign->audience_filters ?? [],
        );

        return view('isp.communications.show', compact(
            'campaign',
            'preview',
            'deliveryLogs',
            'filterLabels',
        ));
    }

    public function launch(CommunicationCampaign $campaign): RedirectResponse
    {
        $this->authorizeCampaign($campaign);

        if (! $campaign->isLaunchable()) {
            return back()->with('error', 'Only draft or scheduled campaigns can be launched.');
        }

        $this->campaigns->launch($campaign);

        return redirect()
            ->route('isp.communications.show', $campaign)
            ->with('success', 'Campaign queued for delivery.');
    }

    public function previewAudience(Request $request): JsonResponse
    {
        $ispId = Auth::user()->isp_id;
        $validated = $request->validate([
            'channel' => 'required|in:sms,email,both',
            'customer_id' => 'nullable|integer',
            'plan_id' => 'nullable|integer',
            'router_id' => 'nullable|integer',
            'connection_type' => 'nullable|in:hotspot,pppoe',
            'status' => 'nullable|in:active,suspended,expired',
            'sms_group_id' => 'nullable|integer',
            'search' => 'nullable|string|max:255',
        ]);

        $filters = $this->buildAudienceFilters($validated);
        $count = $this->audience->count($ispId, $filters, $validated['channel']);
        $sample = $this->audience->resolve($ispId, $filters, $validated['channel'])->take(5);

        return response()->json([
            'count' => $count,
            'sample' => $sample,
        ]);
    }

    protected function validateCampaign(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'message_body' => 'required|string|max:5000',
            'channel' => 'required|in:sms,email,both',
            'customer_id' => 'nullable|integer',
            'plan_id' => 'nullable|integer',
            'router_id' => 'nullable|integer',
            'connection_type' => 'nullable|in:hotspot,pppoe',
            'status' => 'nullable|in:active,suspended,expired',
            'sms_group_id' => 'nullable|integer',
            'search' => 'nullable|string|max:255',
            'send_action' => 'required|in:draft,now,schedule',
            'scheduled_at' => 'nullable|date|after:now|required_if:send_action,schedule',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildAudienceFilters(array $validated): array
    {
        return array_filter([
            'customer_id' => $validated['customer_id'] ?? null,
            'plan_id' => $validated['plan_id'] ?? null,
            'router_id' => $validated['router_id'] ?? null,
            'connection_type' => $validated['connection_type'] ?? null,
            'status' => $validated['status'] ?? null,
            'sms_group_id' => $validated['sms_group_id'] ?? null,
            'search' => $validated['search'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function assertAudienceOwnership(int $ispId, array $filters): void
    {
        if (! empty($filters['customer_id'])) {
            Customer::forIsp($ispId)->whereKey($filters['customer_id'])->firstOrFail();
        }

        if (! empty($filters['plan_id'])) {
            Plan::forIsp($ispId)->whereKey($filters['plan_id'])->firstOrFail();
        }

        if (! empty($filters['router_id'])) {
            Router::forIsp($ispId)->whereKey($filters['router_id'])->firstOrFail();
        }

        if (! empty($filters['sms_group_id'])) {
            SmsGroup::forIsp($ispId)->whereKey($filters['sms_group_id'])->firstOrFail();
        }
    }

    protected function authorizeCampaign(CommunicationCampaign $campaign): void
    {
        if ($campaign->isp_id !== Auth::user()->isp_id) {
            abort(403);
        }
    }
}
