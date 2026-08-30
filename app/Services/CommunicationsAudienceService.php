<?php

namespace App\Services;

use App\Models\CommunicationCampaign;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\SmsGroup;
use App\Support\Search\SearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommunicationsAudienceService
{
    /**
     * Resolve campaign audience into deliverable recipients.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{
     *     customer_id: int,
     *     full_name: string,
     *     phone_number: ?string,
     *     email: ?string,
     *     connection_type: ?string,
     *     router_id: ?int,
     *     plan_id: ?int
     * }>
     */
    public function resolve(int $ispId, array $filters, string $channel): Collection
    {
        $query = $this->baseQuery($ispId, $filters);

        $customers = $query
            ->select([
                'customers.id',
                'customers.full_name',
                'customers.phone_number',
                'customers.email',
                'customers.connection_type',
                'customers.router_id',
            ])
            ->with(['subscriptions' => fn ($q) => $q->where('status', 'active')->latest('end_date')->limit(1)])
            ->get();

        return $customers
            ->map(function (Customer $customer) {
                $activeSub = $customer->subscriptions->first();

                return [
                    'customer_id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $this->normalizePhone($customer->phone_number),
                    'email' => filled($customer->email) ? trim($customer->email) : null,
                    'connection_type' => $customer->connection_type,
                    'router_id' => $customer->router_id,
                    'plan_id' => $activeSub?->plan_id,
                ];
            })
            ->filter(fn (array $row) => $this->hasDeliverableContact($row, $channel))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function count(int $ispId, array $filters, string $channel): int
    {
        return $this->resolve($ispId, $filters, $channel)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    public function describeFilters(int $ispId, array $filters): array
    {
        $labels = [];

        if (! empty($filters['customer_id'])) {
            $customer = Customer::forIsp($ispId)->find($filters['customer_id']);
            $labels['customer'] = $customer?->full_name ?? 'Customer #'.$filters['customer_id'];
        }

        if (! empty($filters['plan_id'])) {
            $plan = Plan::forIsp($ispId)->find($filters['plan_id']);
            $labels['plan'] = $plan?->name ?? 'Plan #'.$filters['plan_id'];
        }

        if (! empty($filters['router_id'])) {
            $router = Router::forIsp($ispId)->find($filters['router_id']);
            $labels['router'] = $router?->name ?? 'Router #'.$filters['router_id'];
        }

        if (! empty($filters['connection_type'])) {
            $labels['connection'] = strtoupper((string) $filters['connection_type']);
        }

        if (! empty($filters['status'])) {
            $labels['status'] = ucfirst((string) $filters['status']);
        }

        if (! empty($filters['sms_group_id'])) {
            $group = SmsGroup::forIsp($ispId)->find($filters['sms_group_id']);
            $labels['group'] = $group?->name ?? 'Group #'.$filters['sms_group_id'];
        }

        if (! empty($filters['search'])) {
            $labels['search'] = (string) $filters['search'];
        }

        return $labels;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function baseQuery(int $ispId, array $filters): Builder
    {
        $query = Customer::query()->forIsp($ispId);

        if (! empty($filters['customer_id'])) {
            $query->whereKey((int) $filters['customer_id']);
        }

        if (! empty($filters['plan_id'])) {
            $planId = (int) $filters['plan_id'];
            $query->whereHas('subscriptions', fn ($q) => $q
                ->where('plan_id', $planId)
                ->where('status', 'active')
                ->where('end_date', '>=', now()->startOfDay()));
        }

        if (! empty($filters['router_id'])) {
            $query->where('router_id', (int) $filters['router_id']);
        }

        if (! empty($filters['connection_type'])) {
            $query->where('connection_type', $filters['connection_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['sms_group_id'])) {
            $groupId = (int) $filters['sms_group_id'];
            $query->whereHas('smsGroups', fn ($q) => $q->where('sms_groups.id', $groupId));
        }

        if (! empty($filters['search'])) {
            $term = SearchHelper::likePattern($filters['search']);
            if ($term !== null) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('full_name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('phone_number', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            }
        }

        return $query->orderBy('full_name');
    }

    /**
     * @param  array{phone_number: ?string, email: ?string}  $row
     */
    protected function hasDeliverableContact(array $row, string $channel): bool
    {
        return match ($channel) {
            CommunicationCampaign::CHANNEL_SMS => filled($row['phone_number']),
            CommunicationCampaign::CHANNEL_EMAIL => filled($row['email']),
            CommunicationCampaign::CHANNEL_BOTH => filled($row['phone_number']) || filled($row['email']),
            default => false,
        };
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        return $phone !== '' ? $phone : null;
    }
}
