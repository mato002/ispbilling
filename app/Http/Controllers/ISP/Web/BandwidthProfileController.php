<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\BandwidthProfile;
use App\Models\Router;
use App\Services\MikroTikService;
use App\Support\Export\CsvExporter;
use App\Support\FormSettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BandwidthProfileController extends Controller
{
    use InteractsWithListings;

    public function __construct(private readonly FormSettingsService $formSettings) {}

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $query = $this->filteredBandwidthProfilesQuery($request, $ispId);
        $perPage = $this->resolvePerPage($request);
        $bandwidthProfiles = $query->paginate($perPage)->withQueryString();

        $routers = Router::forIsp($ispId)->active()->get();

        $filterChips = $this->listingFilterChips($request, route('isp.bandwidth-profiles.index'), [
            'is_active' => [
                'label' => 'Status',
                'values' => ['1' => 'Active', '0' => 'Inactive'],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.bandwidth-profiles.export.csv'));

        return view('isp.bandwidth-profiles.index', compact('bandwidthProfiles', 'routers', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $ispId = auth()->user()->isp_id;
        $query = $this->filteredBandwidthProfilesQuery($request, $ispId)->with('parentProfile');
        $filename = 'bandwidth-profiles-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'Name', 'Download (Kbps)', 'Upload (Kbps)', 'Queue type', 'Priority',
            'Parent profile', 'Active', 'Comment',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($profiles) use ($out) {
                foreach ($profiles as $profile) {
                    fputcsv($out, [
                        $profile->name,
                        $profile->download_limit,
                        $profile->upload_limit,
                        $profile->queue_type,
                        $profile->priority,
                        $profile->parentProfile?->name,
                        $profile->is_active ? 'Active' : 'Inactive',
                        $profile->comment,
                    ]);
                }
            });
        });
    }

    protected function filteredBandwidthProfilesQuery(Request $request, int $ispId): Builder
    {
        return BandwidthProfile::forIsp($ispId)
            ->with('parentProfile')
            ->when($request->filled('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->orderBy('name');
    }

    public function create()
    {
        $ispId = auth()->user()->isp_id;
        $parentProfiles = BandwidthProfile::forIsp($ispId)->active()->orderBy('name')->get();
        $routers = Router::forIsp($ispId)->active()->get();
        $formFields = $this->formSettings->resolvedFields('bandwidth_profile', $ispId);

        return view('isp.bandwidth-profiles.create', compact('parentProfiles', 'routers', 'formFields'));
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $this->formSettings->validateRequest($request, 'bandwidth_profile', [
            'name' => ['string', 'max:255'],
            'download_limit' => ['numeric', 'min:0.1'],
            'upload_limit' => ['numeric', 'min:0.1'],
            'download_burst_limit' => ['nullable', 'numeric', 'min:0.1'],
            'upload_burst_limit' => ['nullable', 'numeric', 'min:0.1'],
            'download_burst_threshold' => ['nullable', 'numeric', 'min:0.1'],
            'upload_burst_threshold' => ['nullable', 'numeric', 'min:0.1'],
            'download_burst_time' => ['nullable', 'integer', 'min:1'],
            'upload_burst_time' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'parent_profile_id' => ['nullable', 'exists:bandwidth_profiles,id'],
            'queue_type' => ['required', 'in:queue-simple,queue-tree'],
            'comment' => ['string', 'max:500'],
        ], $ispId);

        try {
            // Create bandwidth profile in database
            $bandwidthProfile = BandwidthProfile::create([
                'isp_id' => auth()->user()->isp_id,
                'name' => $request->name,
                'download_limit' => (int) round($request->download_limit * 1000), // Mbps → Kbps
                'upload_limit' => (int) round($request->upload_limit * 1000),
                'download_burst_limit' => $request->download_burst_limit ? (int) round($request->download_burst_limit * 1000) : null,
                'upload_burst_limit' => $request->upload_burst_limit ? (int) round($request->upload_burst_limit * 1000) : null,
                'download_burst_threshold' => $request->download_burst_threshold ? (int) round($request->download_burst_threshold * 1000) : null,
                'upload_burst_threshold' => $request->upload_burst_threshold ? (int) round($request->upload_burst_threshold * 1000) : null,
                'download_burst_time' => $request->download_burst_time,
                'upload_burst_time' => $request->upload_burst_time,
                'priority' => $request->priority ?? 8,
                'parent_profile_id' => $request->parent_profile_id,
                'queue_type' => $request->queue_type,
                'comment' => $request->comment,
                'is_active' => true,
            ]);

            return redirect()->route('isp.bandwidth-profiles.index')->with('success', 'Bandwidth profile created successfully');

        } catch (\Exception $e) {
            Log::error('Bandwidth profile creation error: '.$e->getMessage());

            return back()->with('error', 'Failed to create bandwidth profile: '.$e->getMessage())->withInput();
        }
    }

    public function show(BandwidthProfile $bandwidthProfile)
    {
        if ($bandwidthProfile->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $bandwidthProfile->load(['parentProfile', 'childProfiles', 'plans']);
        $routers = Router::forIsp(auth()->user()->isp_id)->active()->get();

        return view('isp.bandwidth-profiles.show', compact('bandwidthProfile', 'routers'));
    }

    public function edit(BandwidthProfile $bandwidthProfile)
    {
        if ($bandwidthProfile->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $ispId = auth()->user()->isp_id;
        $parentProfiles = BandwidthProfile::forIsp($ispId)->active()
            ->where('id', '!=', $bandwidthProfile->id)
            ->orderBy('name')
            ->get();

        $bandwidthProfile->load('parentProfile');
        $formFields = $this->formSettings->resolvedFields('bandwidth_profile', $ispId);

        return view('isp.bandwidth-profiles.edit', compact('bandwidthProfile', 'parentProfiles', 'formFields'));
    }

    public function update(Request $request, BandwidthProfile $bandwidthProfile)
    {
        if ($bandwidthProfile->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $this->formSettings->validateRequest($request, 'bandwidth_profile', [
            'name' => ['string', 'max:255'],
            'download_limit' => ['numeric', 'min:0.1'],
            'upload_limit' => ['numeric', 'min:0.1'],
            'download_burst_limit' => ['nullable', 'numeric', 'min:0.1'],
            'upload_burst_limit' => ['nullable', 'numeric', 'min:0.1'],
            'download_burst_threshold' => ['nullable', 'numeric', 'min:0.1'],
            'upload_burst_threshold' => ['nullable', 'numeric', 'min:0.1'],
            'download_burst_time' => ['nullable', 'integer', 'min:1'],
            'upload_burst_time' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'parent_profile_id' => ['nullable', 'exists:bandwidth_profiles,id'],
            'queue_type' => ['required', 'in:queue-simple,queue-tree'],
            'comment' => ['string', 'max:500'],
        ], $bandwidthProfile->isp_id);

        try {
            $bandwidthProfile->update([
                'name' => $request->name,
                'download_limit' => (int) round($request->download_limit * 1000),
                'upload_limit' => (int) round($request->upload_limit * 1000),
                'download_burst_limit' => $request->download_burst_limit ? (int) round($request->download_burst_limit * 1000) : null,
                'upload_burst_limit' => $request->upload_burst_limit ? (int) round($request->upload_burst_limit * 1000) : null,
                'download_burst_threshold' => $request->download_burst_threshold ? (int) round($request->download_burst_threshold * 1000) : null,
                'upload_burst_threshold' => $request->upload_burst_threshold ? (int) round($request->upload_burst_threshold * 1000) : null,
                'download_burst_time' => $request->download_burst_time,
                'upload_burst_time' => $request->upload_burst_time,
                'priority' => $request->priority ?? 8,
                'parent_profile_id' => $request->parent_profile_id,
                'queue_type' => $request->queue_type,
                'comment' => $request->comment,
            ]);

            return redirect()->route('isp.bandwidth-profiles.index')->with('success', 'Bandwidth profile updated successfully');

        } catch (\Exception $e) {
            Log::error('Bandwidth profile update error: '.$e->getMessage());

            return back()->with('error', 'Failed to update bandwidth profile: '.$e->getMessage())->withInput();
        }
    }

    public function activate(BandwidthProfile $bandwidthProfile)
    {
        if ($bandwidthProfile->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $bandwidthProfile->update(['is_active' => true]);

        return back()->with('success', 'Bandwidth profile activated successfully');
    }

    public function deactivate(BandwidthProfile $bandwidthProfile)
    {
        if ($bandwidthProfile->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        // Check if profile is being used by plans
        if ($bandwidthProfile->plans()->count() > 0) {
            return back()->with('error', 'Cannot deactivate bandwidth profile. It is being used by plans.');
        }

        $bandwidthProfile->update(['is_active' => false]);

        return back()->with('success', 'Bandwidth profile deactivated successfully');
    }

    public function destroy(BandwidthProfile $bandwidthProfile)
    {
        if ($bandwidthProfile->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        // Check if profile is being used by plans or has child profiles
        if ($bandwidthProfile->plans()->count() > 0) {
            return back()->with('error', 'Cannot delete bandwidth profile. It is being used by plans.');
        }

        if ($bandwidthProfile->childProfiles()->count() > 0) {
            return back()->with('error', 'Cannot delete bandwidth profile. It has child profiles.');
        }

        $bandwidthProfile->delete();

        return redirect()->route('isp.bandwidth-profiles.index')->with('success', 'Bandwidth profile deleted successfully');
    }

    /**
     * Get available bandwidth profiles for selection
     */
    public function getAvailable(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $profiles = BandwidthProfile::forIsp($ispId)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'download_speed' => $profile->formatted_download_speed,
                    'upload_speed' => $profile->formatted_upload_speed,
                    'display_name' => "{$profile->name} (↓{$profile->formatted_download_speed}/↑{$profile->formatted_upload_speed})",
                ];
            });

        return response()->json($profiles);
    }

    /**
     * Deploy bandwidth profile to router
     */
    public function deploy(Request $request, BandwidthProfile $bandwidthProfile)
    {
        if ($bandwidthProfile->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $request->validate([
            'router_id' => 'required|exists:routers,id',
        ]);

        $router = Router::findOrFail($request->router_id);

        // Verify router belongs to ISP
        if ($router->isp_id !== auth()->user()->isp_id) {
            return back()->with('error', 'Router not found');
        }

        try {
            $mikrotikService = new MikroTikService($router);

            $config = [
                'name' => $bandwidthProfile->name,
                'download_limit' => $bandwidthProfile->download_limit,
                'upload_limit' => $bandwidthProfile->upload_limit,
                'priority' => $bandwidthProfile->priority,
                'comment' => $bandwidthProfile->comment,
                'target' => $bandwidthProfile->target ?? '',
            ];

            // Add burst settings if configured (values stored as Kbps)
            if ($bandwidthProfile->download_burst_limit && $bandwidthProfile->upload_burst_limit) {
                $dlBurst = (int) $bandwidthProfile->download_burst_limit;
                $ulBurst = (int) $bandwidthProfile->upload_burst_limit;
                $dlThresh = (int) ($bandwidthProfile->download_burst_threshold ?? ($bandwidthProfile->download_limit * 0.8));
                $ulThresh = (int) ($bandwidthProfile->upload_burst_threshold ?? ($bandwidthProfile->upload_limit * 0.8));
                $dlTime = (int) ($bandwidthProfile->download_burst_time ?? 10);
                $ulTime = (int) ($bandwidthProfile->upload_burst_time ?? 10);

                $config['burst_limit'] = $dlBurst.'k/'.$ulBurst.'k';
                $config['burst_threshold'] = $dlThresh.'k/'.$ulThresh.'k';
                $config['burst_time'] = $dlTime.'/'.$ulTime;
            }

            $result = false;
            if ($bandwidthProfile->queue_type === 'queue-tree') {
                $result = $mikrotikService->createQueueTree($config);
            } else {
                $result = $mikrotikService->createQueueSimple($config);
            }

            if ($result) {
                return back()->with('success', 'Bandwidth profile deployed to router successfully');
            } else {
                return back()->with('error', 'Failed to deploy bandwidth profile to router');
            }

        } catch (\Exception $e) {
            Log::error('Bandwidth profile deployment error: '.$e->getMessage());

            return back()->with('error', 'Failed to deploy bandwidth profile: '.$e->getMessage());
        }
    }
}
