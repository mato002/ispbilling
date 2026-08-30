<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Export\CsvExporter;
use App\Support\FormSettingsService;
use App\Support\InvoiceTax;
use App\Support\PlanPackageTypes;
use App\Support\Search\SearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    use InteractsWithListings;

    public function __construct(private readonly FormSettingsService $formSettings) {}

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->orderBy('name')->get();

        $query = $this->filteredSubscriptionsQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $subscriptions = $query->latest()->paginate($perPage)->withQueryString();

        $planLabels = $plans->pluck('name', 'id')->mapWithKeys(
            fn (string $name, int $id): array => [(string) $id => $name]
        )->all();

        $filterChips = $this->listingFilterChips($request, route('isp.subscriptions.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'active' => 'Active',
                    'expired' => 'Expired',
                    'cancelled' => 'Cancelled',
                    'suspended' => 'Suspended',
                ],
            ],
            'plan_id' => [
                'label' => 'Plan',
                'values' => $planLabels,
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.subscriptions.export.csv'));

        return view('isp.subscriptions.index', compact('subscriptions', 'plans', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = auth()->user()->isp_id;
        $query = $this->filteredSubscriptionsQuery($request, $ispId)->latest();
        $filename = 'subscriptions-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'ID', 'Customer', 'Phone', 'Username', 'Plan', 'Status',
            'Start date', 'End date', 'Auto renew', 'Created',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($subscriptions) use ($out) {
                foreach ($subscriptions as $subscription) {
                    fputcsv($out, [
                        $subscription->id,
                        $subscription->customer?->full_name,
                        $subscription->customer?->phone_number,
                        $subscription->customer?->username,
                        $subscription->plan?->name,
                        $subscription->status,
                        $subscription->start_date?->format('Y-m-d'),
                        $subscription->end_date?->format('Y-m-d'),
                        $subscription->auto_renew ? 'Yes' : 'No',
                        $subscription->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    protected function filteredSubscriptionsQuery(Request $request, int $ispId): Builder
    {
        $query = Subscription::whereHas('customer', function ($q) use ($ispId) {
            $q->where('isp_id', $ispId);
        })->with(['customer', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($pattern = SearchHelper::likePattern($request->search)) {
            $query->where(function ($q) use ($pattern) {
                $q->whereHas('customer', function ($c) use ($pattern) {
                    $c->where('full_name', 'like', $pattern)
                        ->orWhere('phone_number', 'like', $pattern)
                        ->orWhere('username', 'like', $pattern);
                })->orWhereHas('plan', function ($p) use ($pattern) {
                    $p->where('name', 'like', $pattern);
                });
            });
        }

        return $query;
    }

    public function create(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $customers = Customer::where('isp_id', $ispId)->orderBy('full_name')->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->orderBy('name')->get();

        $selectedCustomerId = $request->query('customer_id');
        if ($selectedCustomerId && ! Customer::where('isp_id', $ispId)->whereKey($selectedCustomerId)->exists()) {
            $selectedCustomerId = null;
        }

        $formFields = $this->formSettings->resolvedFields('subscription', $ispId);

        return view('isp.subscriptions.create', compact('customers', 'plans', 'selectedCustomerId', 'formFields'));
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $validated = $this->formSettings->validateRequest($request, 'subscription', [
            'customer_id' => ['exists:customers,id'],
            'plan_id' => ['exists:plans,id'],
            'start_date' => ['date'],
            'end_date' => ['date', 'after_or_equal:start_date'],
            'status' => ['in:active,suspended'],
            'auto_renew' => ['boolean'],
            'static_ip' => ['nullable', 'ip'],
            'static_ip_active' => ['nullable', 'boolean'],
            'service_status' => ['nullable', 'in:pending,scheduled,in_progress,completed,cancelled'],
            'parent_subscription_id' => ['nullable', 'exists:subscriptions,id'],
        ], $ispId);

        $customer = Customer::where('isp_id', $ispId)->findOrFail($validated['customer_id']);
        $plan = Plan::where('isp_id', $ispId)->where('is_active', true)->findOrFail($validated['plan_id']);

        if ($plan->isStaticIpAddon()) {
            $request->validate(['static_ip' => 'required|ip']);
        }

        if ($validated['status'] === 'active' && $plan->isConnectionPackage()) {
            Subscription::where('customer_id', $customer->id)
                ->where('status', 'active')
                ->whereHas('plan', fn ($q) => $q->whereIn('package_type', PlanPackageTypes::connectionPackages()))
                ->update(['status' => 'cancelled', 'auto_renew' => false]);
        }

        $subscriptionPayload = [
            'customer_id' => $customer->id,
            'plan_id' => $validated['plan_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'renewal_date' => $validated['end_date'],
            'status' => $validated['status'],
            'auto_renew' => $request->boolean('auto_renew'),
        ];

        $subscriptionPayload = array_merge(
            $subscriptionPayload,
            PlanPackageTypes::subscriptionDefaultsForPlan($plan)
        );

        if ($plan->isStaticIpAddon() && $request->filled('static_ip')) {
            $subscriptionPayload['static_ip'] = $request->input('static_ip');
            $subscriptionPayload['static_ip_active'] = $request->boolean('static_ip_active', true);
        }

        if ($request->filled('service_status')) {
            $subscriptionPayload['service_status'] = $request->input('service_status');
        }

        if ($request->filled('parent_subscription_id')) {
            $subscriptionPayload['parent_subscription_id'] = $request->input('parent_subscription_id');
        } elseif ($plan->requires_pppoe_plan) {
            $parent = Subscription::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->whereHas('plan', fn ($q) => $q->whereIn('package_type', [PlanPackageTypes::PPPOE, PlanPackageTypes::UNLIMITED, PlanPackageTypes::DATA_BUNDLE]))
                ->latest('id')
                ->first();
            if ($parent) {
                $subscriptionPayload['parent_subscription_id'] = $parent->id;
            }
        }

        $subscription = Subscription::create($subscriptionPayload);

        $message = 'Subscription created successfully.';
        if ($plan->isConnectionPackage() && $customer->router) {
            try {
                $mikrotik = new \App\Services\MikroTikService($customer->router);
                if ($customer->mikrotik_user_id) {
                    $mikrotik->updateUserProfile($customer, $plan);
                }
                if ($validated['status'] === 'active') {
                    $mikrotik->enableUser($customer);
                    if ($customer->status !== 'active') {
                        $customer->activate();
                    }
                } elseif ($validated['status'] === 'suspended') {
                    $mikrotik->disableUser($customer);
                    $customer->suspend();
                }
            } catch (\Throwable $e) {
                \Log::warning('Subscription store: MikroTik sync failed', [
                    'subscription_id' => $subscription->id,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                $message = 'Subscription created, but router sync failed: '.$e->getMessage();
            }
        }

        return redirect()->route('isp.subscriptions.show', $subscription)
            ->with($message === 'Subscription created successfully.' ? 'success' : 'warning', $message);
    }

    public function show(Subscription $subscription)
    {
        if ($subscription->customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $subscription->load(['customer', 'plan', 'invoices', 'parentSubscription']);

        return view('isp.subscriptions.show', compact('subscription'));
    }

    public function renew(Subscription $subscription)
    {
        if ($subscription->customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $subscription->load(['customer.isp', 'plan']);

        if (! in_array($subscription->status, ['active', 'expired', 'suspended'], true)) {
            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('error', 'Only active, expired, or suspended subscriptions can be renewed.');
        }

        $existing = Invoice::query()
            ->where('subscription_id', $subscription->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->latest('id')
            ->first();

        if ($existing) {
            return redirect()
                ->route('isp.invoices.show', $existing)
                ->with('info', 'A pending invoice already exists for this subscription. Collect payment to renew service.');
        }

        $plan = $subscription->plan;
        if (! $plan) {
            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('error', 'Subscription plan is missing.');
        }

        $customer = $subscription->customer;
        $lines = InvoiceTax::computeForAmount((float) $plan->price, $customer->isp);

        $invoice = Invoice::create([
            'isp_id' => $customer->isp_id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'amount' => $lines['amount'],
            'tax' => $lines['tax'],
            'total_amount' => $lines['total_amount'],
            'issue_date' => now(),
            'due_date' => now(),
            'status' => 'pending',
            'notes' => 'Renewal invoice for plan '.$plan->name,
        ]);

        return redirect()
            ->route('isp.invoices.show', $invoice)
            ->with('success', 'Renewal invoice created. Collect payment to extend the subscription.');
    }

    public function cancel(Subscription $subscription)
    {
        if ($subscription->customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        try {
            $customer = $subscription->customer;
            $wasConnectionPackage = (bool) $subscription->plan?->isConnectionPackage();

            $subscription->cancel();

            // Disable router access only when no other active connection package remains.
            if ($wasConnectionPackage && $customer && $customer->router) {
                $stillHasConnection = Subscription::query()
                    ->where('customer_id', $customer->id)
                    ->where('status', 'active')
                    ->whereHas('plan', fn ($q) => $q->whereIn('package_type', PlanPackageTypes::connectionPackages()))
                    ->exists();

                if (! $stillHasConnection) {
                    try {
                        $mikrotik = new \App\Services\MikroTikService($customer->router);
                        $mikrotik->disconnectSession($customer);
                        $mikrotik->disableUser($customer);
                        $customer->suspend();
                    } catch (\Exception $e) {
                        \Log::warning('Failed to suspend customer on router during subscription cancellation: '.$e->getMessage());
                    }
                }
            }

            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('success', 'Subscription cancelled successfully.');
        } catch (\Exception $e) {
            return redirect()->route('isp.subscriptions.show', $subscription)
                ->with('error', 'Failed to cancel subscription: '.$e->getMessage());
        }
    }
}
