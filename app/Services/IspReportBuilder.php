<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class IspReportBuilder
{
    /** Whitelisted sources and columns. Expressions are application-owned SQL only. */
    public function definitions(): array
    {
        return [
            'customers' => [
                'label' => 'Customers', 'table' => 'customers as c', 'id' => 'c.id', 'tenant' => 'c.isp_id', 'date' => 'c.created_at',
                'status' => ['active', 'suspended', 'expired', 'inactive'],
                'columns' => [
                    'name' => ['label' => 'Customer', 'sql' => 'c.full_name', 'group' => true, 'search' => true],
                    'phone' => ['label' => 'Phone', 'sql' => 'c.phone_number', 'search' => true],
                    'email' => ['label' => 'Email', 'sql' => 'c.email', 'search' => true],
                    'location' => ['label' => 'Location', 'sql' => 'c.location', 'group' => true, 'search' => true],
                    'connection_type' => ['label' => 'Connection type', 'sql' => 'c.connection_type', 'group' => true],
                    'status' => ['label' => 'Status', 'sql' => 'c.status', 'group' => true],
                    'installation_date' => ['label' => 'Installation date', 'sql' => 'c.installation_date'],
                    'created_at' => ['label' => 'Created', 'sql' => 'c.created_at'],
                ],
                'measures' => [],
            ],
            'invoices' => [
                'label' => 'Invoices', 'table' => 'invoices as i', 'id' => 'i.id', 'tenant' => 'i.isp_id', 'date' => 'i.issue_date',
                'joins' => [['customers as c', 'c.id', '=', 'i.customer_id']],
                'status' => ['pending', 'paid', 'overdue', 'cancelled'],
                'columns' => [
                    'invoice_number' => ['label' => 'Invoice number', 'sql' => 'i.invoice_number', 'search' => true],
                    'customer' => ['label' => 'Customer', 'sql' => 'c.full_name', 'group' => true, 'search' => true],
                    'phone' => ['label' => 'Phone', 'sql' => 'c.phone_number', 'search' => true],
                    'status' => ['label' => 'Status', 'sql' => 'i.status', 'group' => true],
                    'amount' => ['label' => 'Amount', 'sql' => 'i.amount', 'numeric' => true],
                    'tax' => ['label' => 'Tax', 'sql' => 'i.tax', 'numeric' => true],
                    'total_amount' => ['label' => 'Total', 'sql' => 'i.total_amount', 'numeric' => true],
                    'issue_date' => ['label' => 'Issue date', 'sql' => 'i.issue_date'],
                    'due_date' => ['label' => 'Due date', 'sql' => 'i.due_date'],
                    'paid_date' => ['label' => 'Paid date', 'sql' => 'i.paid_date'],
                ],
                'measures' => ['amount' => 'i.amount', 'tax' => 'i.tax', 'total_amount' => 'i.total_amount'],
            ],
            'payments' => [
                'label' => 'Payments', 'table' => 'payments as p', 'id' => 'p.id', 'tenant' => 'p.isp_id', 'date' => 'COALESCE(p.processed_at, p.created_at)',
                'joins' => [['customers as c', 'c.id', '=', 'p.customer_id']],
                'status' => ['pending', 'completed', 'failed', 'cancelled'],
                'columns' => [
                    'reference' => ['label' => 'Reference', 'sql' => 'p.payment_reference', 'search' => true],
                    'customer' => ['label' => 'Customer', 'sql' => 'c.full_name', 'group' => true, 'search' => true],
                    'phone' => ['label' => 'Phone', 'sql' => 'p.phone_number'],
                    'method' => ['label' => 'Payment method', 'sql' => 'p.payment_method', 'group' => true],
                    'status' => ['label' => 'Status', 'sql' => 'p.status', 'group' => true],
                    'amount' => ['label' => 'Amount', 'sql' => 'p.amount', 'numeric' => true],
                    'processed_at' => ['label' => 'Processed at', 'sql' => 'p.processed_at'],
                    'created_at' => ['label' => 'Created', 'sql' => 'p.created_at'],
                ],
                'measures' => ['amount' => 'p.amount'],
            ],
            'subscriptions' => [
                'label' => 'Subscriptions', 'table' => 'subscriptions as s', 'id' => 's.id', 'tenant' => 'c.isp_id', 'date' => 's.start_date',
                'joins' => [['customers as c', 'c.id', '=', 's.customer_id'], ['plans as pl', 'pl.id', '=', 's.plan_id']],
                'status' => ['active', 'expired', 'cancelled', 'suspended'],
                'columns' => [
                    'customer' => ['label' => 'Customer', 'sql' => 'c.full_name', 'group' => true, 'search' => true],
                    'phone' => ['label' => 'Phone', 'sql' => 'c.phone_number'],
                    'plan' => ['label' => 'Plan', 'sql' => 'pl.name', 'group' => true, 'search' => true],
                    'status' => ['label' => 'Status', 'sql' => 's.status', 'group' => true],
                    'start_date' => ['label' => 'Start date', 'sql' => 's.start_date'],
                    'end_date' => ['label' => 'End date', 'sql' => 's.end_date'],
                    'renewal_date' => ['label' => 'Renewal date', 'sql' => 's.renewal_date'],
                    'auto_renew' => ['label' => 'Auto renew', 'sql' => 's.auto_renew'],
                ],
                'measures' => [],
            ],
        ];
    }

    public function query(int $ispId, array $input): array
    {
        $definitions = $this->definitions();
        $dataset = $definitions[$input['dataset']];
        $query = DB::table($dataset['table'])->where($dataset['tenant'], $ispId);
        foreach ($dataset['joins'] ?? [] as [$table, $first, $operator, $second]) {
            $query->join($table, $first, $operator, $second);
        }

        if (! empty($input['status']) && in_array($input['status'], $dataset['status'], true)) {
            $query->where($dataset['columns']['status']['sql'], $input['status']);
        }
        if (! empty($input['date_from'])) $query->where($dataset['date'], '>=', $input['date_from'].' 00:00:00');
        if (! empty($input['date_to'])) $query->where($dataset['date'], '<=', $input['date_to'].' 23:59:59');
        if (! empty($input['search'])) {
            $searchable = array_filter($dataset['columns'], fn ($column) => ! empty($column['search']));
            $pattern = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $input['search']).'%';
            $query->where(function (Builder $nested) use ($searchable, $pattern) {
                foreach ($searchable as $column) $nested->orWhere($column['sql'], 'like', $pattern);
            });
        }

        $groupBy = $input['group_by'] ?? '';
        $metric = $input['metric'] ?? 'count';
        $measure = $input['measure'] ?? '';
        if ($groupBy !== '') {
            $groupColumn = $dataset['columns'][$groupBy];
            $query->select(DB::raw($groupColumn['sql'].' as group_value'))
                ->groupBy($groupColumn['sql'])
                ->orderBy($groupColumn['sql']);
        }
        if ($metric === 'count') {
            $query->addSelect(DB::raw('COUNT(*) as metric_value'));
        } elseif (in_array($metric, ['sum', 'average'], true) && isset($dataset['measures'][$measure])) {
            $function = $metric === 'sum' ? 'SUM' : 'AVG';
            $query->addSelect(DB::raw($function.'('.$dataset['measures'][$measure].') as metric_value'));
        }

        if ($groupBy === '') {
            $row = $query->first();
            return [[$input['metric_label'] ?? ucfirst($metric), (float) ($row->metric_value ?? 0)]];
        }

        return $query->get()->map(fn ($row) => [$row->group_value ?? '(empty)', (float) $row->metric_value])->all();
    }

    public function rows(int $ispId, array $input)
    {
        $dataset = $this->definitions()[$input['dataset']];
        $query = DB::table($dataset['table'])->where($dataset['tenant'], $ispId);
        foreach ($dataset['joins'] ?? [] as [$table, $first, $operator, $second]) $query->join($table, $first, $operator, $second);
        if (! empty($input['status']) && in_array($input['status'], $dataset['status'], true)) $query->where($dataset['columns']['status']['sql'], $input['status']);
        if (! empty($input['date_from'])) $query->where($dataset['date'], '>=', $input['date_from'].' 00:00:00');
        if (! empty($input['date_to'])) $query->where($dataset['date'], '<=', $input['date_to'].' 23:59:59');
        if (! empty($input['search'])) {
            $searchable = array_filter($dataset['columns'], fn ($column) => ! empty($column['search']));
            $pattern = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $input['search']).'%';
            $query->where(function (Builder $nested) use ($searchable, $pattern) {
                foreach ($searchable as $column) $nested->orWhere($column['sql'], 'like', $pattern);
            });
        }
        $selected = array_intersect_key($dataset['columns'], array_flip($input['columns']));
        foreach ($selected as $key => $column) $query->addSelect(DB::raw($column['sql'].' as `'.$key.'`'));
        return [$query->orderBy($dataset['id']), $selected];
    }
}
