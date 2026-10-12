<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesModalFormResponses;
use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Models\Customer;
use App\Models\HotspotSession;
use App\Models\ISP;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Services\ActivityLogService;
use App\Services\MikroTikService;
use App\Services\NotificationService;
use App\Services\DataUsageMonitoringService;
use App\Support\FormSettingsService;
use App\Support\IspCustomFields;
use App\Support\PlanPackageTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    use HandlesModalFormResponses;
    use InteractsWithListings;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly FormSettingsService $formSettings,
        private readonly DataUsageMonitoringService $dataUsageMonitoring,
    ) {}

    public function index(Request $request)
    {
        $ispId = auth()->user()->isp_id;

        $stats = $this->subscriberStats($ispId);

        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->orderBy('name')->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->primarySellable()->orderBy('name')->get();

        $recentActivity = Payment::where('isp_id', $ispId)
            ->with('customer')
            ->where('status', 'completed')
            ->latest('processed_at')
            ->take(6)
            ->get();

        $query = $this->buildCustomerIndexQuery($request, $ispId);

        if ($request->query('export') === 'csv') {
            return $this->exportCustomersCsv($query->clone(), $request);
        }

        $perPage = $this->resolvePerPage($request);
        $customers = $query->latest()->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.customers.index'), [
            'search' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'expired' => 'Expired',
                    'inactive' => 'Inactive',
                    'pending_installation' => 'Pending installation',
                ],
            ],
            'connection_type' => [
                'label' => 'Connection',
                'values' => ['hotspot' => 'Hotspot', 'pppoe' => 'PPPoE'],
            ],
            'router_id' => [
                'label' => 'Router',
                'values' => $routers->pluck('name', 'id')->all(),
            ],
            'plan_id' => [
                'label' => 'Plan',
                'values' => $plans->pluck('name', 'id')->all(),
            ],
            'created_from' => 'Created from',
            'created_to' => 'Created to',
        ]);

        $exportFormats = $this->listingExportFormats(
            $request,
            route('isp.customers.index', ['export' => 'csv']),
        );

        return view('isp.customers.index', compact(
            'customers',
            'routers',
            'plans',
            'stats',
            'recentActivity',
            'filterChips',
            'exportFormats',
        ));
    }

    /**
     * Aggregate KPIs for the subscriber operations strip (full ISP, not filtered).
     */
    protected function subscriberStats(int $ispId): array
    {
        return [
            'total' => Customer::where('isp_id', $ispId)->count(),
            'active' => Customer::where('isp_id', $ispId)->where('status', 'active')->count(),
            'suspended' => Customer::where('isp_id', $ispId)->where('status', 'suspended')->count(),
            'hotspot' => Customer::where('isp_id', $ispId)->where('connection_type', 'hotspot')->count(),
            'pppoe' => Customer::where('isp_id', $ispId)->where('connection_type', 'pppoe')->count(),
            'expiring_soon' => Customer::where('isp_id', $ispId)->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active')
                    ->whereBetween('end_date', [now(), now()->addDays(7)]);
            })->count(),
            'unpaid' => Customer::where('isp_id', $ispId)->whereHas('invoices', function ($q) {
                $q->whereIn('status', ['pending', 'overdue']);
            })->count(),
            'online_sessions' => HotspotSession::where('isp_id', $ispId)->where('status', 'active')->count(),
        ];
    }

    /**
     * Filtered customer listing query with telecom intelligence eager-loads.
     */
    protected function buildCustomerIndexQuery(Request $request, int $ispId)
    {
        $query = Customer::where('isp_id', $ispId)
            ->with([
                'router',
                'subscriptions' => function ($q) {
                    $q->where('status', 'active')
                        ->where('end_date', '>=', now())
                        ->with('plan')
                        ->latest();
                },
                'payments' => function ($q) {
                    $q->where('status', 'completed')->latest('processed_at')->limit(1);
                },
                'hotspotSessions' => function ($q) {
                    $q->where('status', 'active')->latest('started_at')->limit(1);
                },
                'devices' => function ($q) {
                    $q->orderByDesc('last_seen_at')->limit(3);
                },
            ])
            ->withSum(['invoices as overdue_amount' => function ($q) {
                $q->where('status', 'overdue');
            }], 'total_amount')
            ->withSum(['invoices as pending_amount' => function ($q) {
                $q->where('status', 'pending');
            }], 'total_amount')
            ->withCount(['invoices', 'payments']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('router', function ($r) use ($search) {
                        $r->where('name', 'like', "%{$search}%")
                            ->orWhere('ip_address', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoices', function ($inv) use ($search) {
                        $inv->where('invoice_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('hotspotSessions', function ($h) use ($search) {
                        $h->where('ip_address', 'like', "%{$search}%")
                            ->orWhere('mac_address', 'like', "%{$search}%");
                    })
                    ->orWhereHas('devices', function ($d) use ($search) {
                        $d->where('mac_address', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('connection_type')) {
            $query->where('connection_type', $request->connection_type);
        }

        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        }

        if ($request->filled('plan_id')) {
            $planId = $request->plan_id;
            $query->whereHas('subscriptions', function ($q) use ($planId) {
                $q->where('plan_id', $planId)
                    ->where('status', 'active')
                    ->where('end_date', '>=', now());
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.trim((string) $request->location).'%');
        }

        if ($request->filled('payment_status')) {
            match ($request->payment_status) {
                'overdue' => $query->whereHas('invoices', fn ($q) => $q->where('status', 'overdue')),
                'pending' => $query->whereHas('invoices', fn ($q) => $q->where('status', 'pending')),
                'clear' => $query->whereDoesntHave('invoices', fn ($q) => $q->whereIn('status', ['pending', 'overdue'])),
                default => null,
            };
        }

        if ($request->boolean('online_only')) {
            $query->whereHas('hotspotSessions', fn ($q) => $q->where('status', 'active'));
        }

        if ($request->boolean('expiring_only')) {
            $query->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active')
                    ->whereBetween('end_date', [now(), now()->addDays(7)]);
            });
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        return $query;
    }

    protected function exportCustomersCsv($query, Request $request): StreamedResponse
    {
        $filename = 'primebilling-customers-'.now()->format('Y-m-d-His').'.csv';

        $rows = $query->with(['router', 'subscriptions.plan'])->orderByDesc('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID', 'Full Name', 'Username', 'Phone', 'Email', 'Status', 'Connection',
                'Router', 'Plan', 'Location', 'Created',
            ]);
            foreach ($rows as $c) {
                $sub = $c->subscriptions->first();
                $planName = $sub?->plan?->name ?? '';
                fputcsv($out, [
                    $c->id,
                    $c->full_name,
                    $c->username,
                    $c->phone_number,
                    $c->email ?? '',
                    $c->status,
                    $c->connection_type,
                    $c->router?->name ?? '',
                    $planName,
                    $c->location,
                    $c->created_at?->toIso8601String(),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create()
    {
        $ispId = auth()->user()->isp_id;
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->primarySellable()->get();
        $isp = ISP::findOrFail($ispId);
        $customFieldDefinitions = IspCustomFields::definitionsFor($isp, 'customer');
        $formFields = $this->formSettings->resolvedFields('customer', $ispId);

        return view('isp.customers.create', compact('routers', 'plans', 'customFieldDefinitions', 'formFields'));
    }

    public function store(Request $request)
    {
        $ispId = auth()->user()->isp_id;
        $isp = ISP::with('saasPlan')->findOrFail($ispId);

        $validated = $this->formSettings->validateRequest($request, 'customer', [
            'full_name' => ['string', 'max:255'],
            'phone_number' => ['string', 'max:20'],
            'email' => ['email'],
            'location' => ['string'],
            'connection_type' => ['in:hotspot,pppoe'],
            'router_id' => ['exists:routers,id'],
            'plan_id' => ['exists:plans,id'],
            'username' => ['string', 'max:255', 'unique:customers,username'],
            'password' => ['string', 'min:6'],
            'installation_date' => ['date'],
            'notes' => ['string'],
        ], $ispId);

        $customAttributes = IspCustomFields::validateAndExtract($request, $isp, 'customer');

        $plan = Plan::findOrFail($validated['plan_id']);
        $router = Router::findOrFail($validated['router_id']);
        if ($router->isp_id !== $ispId || $plan->isp_id !== $ispId) {
            abort(403);
        }

        $currentCount = Customer::where('isp_id', $ispId)->count();
        if ($isp->saasPlan && ! $isp->saasPlan->allowsCustomerCount($currentCount + 1)) {
            return back()->withInput()->with(
                'error',
                'You have reached the maximum number of subscribers ('.$isp->saasPlan->max_customers.') on your current PrimeBilling plan. Upgrade under System → PrimeBilling subscription.'
            );
        }

        $plainPassword = ! empty($validated['password'])
            ? $validated['password']
            : Str::password(12);

        try {
            DB::beginTransaction();

            $customer = Customer::create([
                'isp_id' => $ispId,
                'router_id' => $validated['router_id'],
                'full_name' => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'location' => $validated['location'],
                'connection_type' => $validated['connection_type'],
                'username' => $validated['username'] ?? null,
                'password' => Hash::make($plainPassword),
                'status' => 'inactive',
                'installation_date' => $validated['installation_date'],
                'notes' => $validated['notes'] ?? null,
                'custom_attributes' => $customAttributes ?: null,
            ]);

            $mikrotik = new MikroTikService($router);
            $created = $validated['connection_type'] === 'hotspot'
                ? $mikrotik->createHotspotUser($customer, $plan, $plainPassword)
                : $mikrotik->createPppoeUser($customer, $plan, $plainPassword);

            if (! $created) {
                throw new \RuntimeException('Failed to create user on router');
            }

            Subscription::create(array_merge([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => now()->addDays((int) $plan->duration_days),
                'ends_at' => now()->addDays((int) $plan->duration_days),
                'renewal_date' => now()->addDays((int) $plan->duration_days),
                'status' => 'active',
                'auto_renew' => true,
            ], PlanPackageTypes::subscriptionDefaultsForPlan($plan)));

            $mikrotik->enableUser($customer);
            $customer->activate();

            DB::commit();

            ActivityLogService::logCreate($customer, 'Created customer: '.$customer->full_name);

            try {
                $this->notificationService->newCustomer($customer);
            } catch (\Throwable $e) {
                Log::warning('Failed to create ISP new-customer notification', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->modalOrRedirect(
                'Customer created and activated successfully.',
                redirect()->route('isp.customers.show', $customer),
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Failed to create customer: '.$e->getMessage());
        }
    }

    public function show(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $customer->load([
            'router',
            'inventoryItem',
            'activeInventoryAssignments.item',
            'subscriptions' => fn ($q) => $q->with('plan')->orderByDesc('end_date')->limit(25),
            'invoices' => fn ($q) => $q->latest('issue_date')->limit(15),
            'payments' => fn ($q) => $q->latest('processed_at')->limit(15),
        ]);

        $activeSubscription = $customer->subscriptions->first(
            fn (Subscription $s) => $s->status === 'active'
                && $s->end_date->greaterThanOrEqualTo(now()->startOfDay())
        );

        $usageBreakdown = $this->dataUsageMonitoring->customerBreakdown($customer);

        return view('isp.customers.show', compact('customer', 'activeSubscription', 'usageBreakdown'));
    }

    public function edit(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }
        $ispId = auth()->user()->isp_id;
        $routers = Router::where('isp_id', $ispId)->where('is_active', true)->get();
        $plans = Plan::where('isp_id', $ispId)->where('is_active', true)->primarySellable()->get();
        $isp = ISP::findOrFail($ispId);
        $customFieldDefinitions = IspCustomFields::definitionsFor($isp, 'customer');
        $formFields = $this->formSettings->resolvedFields('customer', $ispId);

        return view('isp.customers.edit', compact('customer', 'routers', 'plans', 'customFieldDefinitions', 'formFields'));
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $isp = ISP::findOrFail($customer->isp_id);
        $ispId = $customer->isp_id;
        $oldValues = $customer->only([
            'full_name', 'phone_number', 'email', 'location', 'connection_type',
            'router_id', 'username', 'status', 'installation_date', 'notes',
        ]);
        $oldRouterId = $customer->router_id;
        $oldConnectionType = $customer->connection_type;
        $oldUsername = $customer->username;
        $oldMikrotikUserId = $customer->mikrotik_user_id;

        $validated = $this->formSettings->validateRequest($request, 'customer', [
            'full_name' => ['string', 'max:255'],
            'phone_number' => ['string', 'max:20'],
            'email' => ['email'],
            'location' => ['string'],
            'connection_type' => ['in:hotspot,pppoe'],
            'router_id' => ['exists:routers,id'],
            'username' => ['string', 'max:255', 'unique:customers,username,'.$customer->id],
            'password' => ['string', 'min:6'],
            'installation_date' => ['date'],
            'notes' => ['string'],
        ], $ispId);

        $statusData = $request->validate([
            'status' => ['required', 'in:active,suspended,expired,inactive,pending_installation'],
        ]);

        $customAttributes = IspCustomFields::validateAndExtract($request, $isp, 'customer');

        if (array_key_exists('router_id', $validated)) {
            $router = Router::findOrFail($validated['router_id']);
            if ($router->isp_id !== $customer->isp_id) {
                abort(403);
            }
        }

        $data = array_merge(
            Arr::only($validated, [
                'full_name', 'phone_number', 'email', 'location', 'connection_type',
                'router_id', 'username', 'installation_date', 'notes',
            ]),
            ['status' => $statusData['status']]
        );

        $plainPassword = ! empty($validated['password']) ? $validated['password'] : null;
        if ($plainPassword) {
            $data['password'] = Hash::make($plainPassword);
        }

        $mergedCustom = array_merge($customer->custom_attributes ?? [], $customAttributes);
        $data['custom_attributes'] = $mergedCustom ?: null;

        $customer->update($data);
        $customer->refresh();

        $routerChanged = (int) $oldRouterId !== (int) $customer->router_id;
        $connectionChanged = $oldConnectionType !== $customer->connection_type;
        $usernameChanged = $oldUsername !== $customer->username;
        $statusChanged = ($oldValues['status'] ?? null) !== $customer->status;
        $syncWarning = null;

        try {
            $activePlan = $customer->subscriptions()
                ->where('status', 'active')
                ->with('plan')
                ->latest('id')
                ->first()
                ?->plan;

            if ($routerChanged || $connectionChanged) {
                if ($oldRouterId && $oldMikrotikUserId) {
                    $oldRouter = Router::find($oldRouterId);
                    if ($oldRouter) {
                        $oldMt = new MikroTikService($oldRouter);
                        $oldMt->removeUserByDotId((string) $oldMikrotikUserId, $oldConnectionType ?? 'pppoe');
                    }
                }

                $customer->forceFill(['mikrotik_user_id' => null])->saveQuietly();

                if ($customer->router && $activePlan && $plainPassword) {
                    $mikrotik = new MikroTikService($customer->router);
                    $created = $customer->connection_type === 'hotspot'
                        ? $mikrotik->createHotspotUser($customer, $activePlan, $plainPassword)
                        : $mikrotik->createPppoeUser($customer, $activePlan, $plainPassword);

                    if (! $created) {
                        $syncWarning = 'Customer saved, but recreating the router user failed.';
                    } elseif ($customer->status === 'active') {
                        $mikrotik->enableUser($customer);
                    } elseif (in_array($customer->status, ['suspended', 'expired', 'inactive'], true)) {
                        $mikrotik->disableUser($customer);
                    }
                } elseif ($customer->router && $activePlan && ! $plainPassword) {
                    $syncWarning = 'Customer saved. Provide a password to reprovision this subscriber on the new router.';
                }
            } elseif ($customer->router && ($plainPassword || $usernameChanged || $statusChanged)) {
                $mikrotik = new MikroTikService($customer->router);

                if ($plainPassword || $usernameChanged) {
                    $synced = $mikrotik->syncUserCredentials($customer, $plainPassword, $activePlan);
                    if (! $synced) {
                        $syncWarning = 'Customer saved, but router credential sync failed.';
                    }
                }

                if ($customer->status === 'active') {
                    $mikrotik->enableUser($customer);
                } elseif (in_array($customer->status, ['suspended', 'expired', 'inactive'], true)) {
                    $mikrotik->disableUser($customer);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Customer update: MikroTik sync failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            $syncWarning = 'Customer saved, but router sync failed: '.$e->getMessage();
        }

        ActivityLogService::logUpdate(
            $customer,
            'Updated customer: '.$customer->full_name,
            $oldValues
        );

        $redirect = redirect()->route('isp.customers.show', $customer);
        if ($syncWarning) {
            return $redirect->with('warning', $syncWarning);
        }

        return $redirect->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        $request->validate([
            'confirm_username' => ['required', 'string', Rule::in([$customer->username])],
        ]);

        try {
            if ($customer->router && $customer->mikrotik_user_id) {
                try {
                    $mikrotik = new MikroTikService($customer->router);
                    $mikrotik->disconnectSession($customer);
                    $mikrotik->removeUser($customer);
                } catch (\Throwable $e) {
                    \Log::warning('Customer delete: MikroTik cleanup failed', [
                        'customer_id' => $customer->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $snapshotName = $customer->full_name;
            $snapshotUsername = $customer->username;
            $snapshotId = $customer->id;
            $customer->delete();

            ActivityLogService::logAction(
                'delete',
                'Deleted customer: '.$snapshotName.' ('.$snapshotUsername.')',
                ['customer_id' => $snapshotId, 'username' => $snapshotUsername]
            );

            return redirect()->route('isp.customers.index')
                ->with('success', 'Customer deleted successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete customer: '.$e->getMessage());
        }
    }

    /**
     * Suspend customer
     */
    public function suspend(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if (! $customer->router) {
            return back()->with('error', 'No router assigned to this customer.');
        }

        try {
            $mikrotik = new \App\Services\MikroTikService($customer->router);
            $mikrotik->disconnectSession($customer);
            $mikrotik->disableUser($customer);
            $customer->suspend();

            ActivityLogService::logAction(
                'customer_suspend',
                'Suspended customer: '.$customer->full_name,
                ['customer_id' => $customer->id]
            );

            return back()->with('success', 'Customer suspended successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to suspend customer: '.$e->getMessage());
        }
    }

    /**
     * Activate customer
     */
    public function activate(Customer $customer)
    {
        if ($customer->isp_id !== auth()->user()->isp_id) {
            abort(403);
        }

        if (! $customer->router) {
            return back()->with('error', 'No router assigned to this customer.');
        }

        try {
            $mikrotik = new \App\Services\MikroTikService($customer->router);
            if ($customer->connection_type === 'hotspot') {
                $mikrotik->activateHotspotUser($customer);
            } else {
                $mikrotik->enableUser($customer);
            }
            $customer->activate();

            ActivityLogService::logAction(
                'customer_activate',
                'Activated customer: '.$customer->full_name,
                ['customer_id' => $customer->id]
            );

            return back()->with('success', 'Customer activated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to activate customer: '.$e->getMessage());
        }
    }
}
