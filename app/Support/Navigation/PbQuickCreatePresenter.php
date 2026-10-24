<?php

namespace App\Support\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Context-aware "Create" actions for the admin topbar (jana-prints quick-create pattern).
 */
class PbQuickCreatePresenter
{
    /**
     * @return list<array{label: string, href: string, modal: bool}>
     */
    public function forRoute(?string $routeName, ?User $user): array
    {
        if ($routeName === null || $routeName === '' || ! $user) {
            return [];
        }

        foreach ($this->definitions($user) as $prefix => $items) {
            if (! str_starts_with($routeName, $prefix)) {
                continue;
            }

            return $this->presentItems($items);
        }

        return [];
    }

    /**
     * @return array<string, list<array{label: string, route: string, params?: array<string, mixed>, modal?: bool}>>
     */
    protected function definitions(User $user): array
    {
        $isp = [];

        if ($user->isIspAdmin() || $user->isIspStaff()) {
            $isp = [
                'isp.customers' => [
                    ['label' => 'New customer', 'route' => 'isp.customers.create'],
                ],
                'isp.plans' => [
                    ['label' => 'New plan', 'route' => 'isp.plans.create'],
                ],
                'isp.routers' => [
                    ['label' => 'Add router', 'route' => 'isp.routers.create'],
                ],
                'isp.subscriptions' => [
                    ['label' => 'New subscription', 'route' => 'isp.subscriptions.create'],
                ],
                'isp.invoices' => [
                    ['label' => 'Create invoice', 'route' => 'isp.invoices.index', 'modal' => false],
                ],
                'isp.vouchers' => [
                    ['label' => 'New voucher', 'route' => 'isp.vouchers.create'],
                    ['label' => 'Bulk vouchers', 'route' => 'isp.vouchers.create-bulk'],
                ],
                'isp.ip-pools' => [
                    ['label' => 'New IP pool', 'route' => 'isp.ip-pools.create'],
                ],
                'isp.bandwidth-profiles' => [
                    ['label' => 'New profile', 'route' => 'isp.bandwidth-profiles.create'],
                ],
                'isp.helpdesk' => $user->isIspAdmin()
                    ? [['label' => 'New platform ticket', 'route' => 'isp.helpdesk.create']]
                    : [],
                'isp.tickets' => [
                    ['label' => 'New ticket', 'route' => 'isp.tickets.create'],
                ],
            ];
        }

        $admin = [];

        if ($user->isSuperAdmin()) {
            $admin = [
                'admin.isps' => [
                    ['label' => 'Add ISP', 'route' => 'admin.isps.create'],
                ],
                'admin.users' => [
                    ['label' => 'Add user', 'route' => 'admin.users.create'],
                ],
                'admin.platform-saas-plans' => [
                    ['label' => 'Create SaaS plan', 'route' => 'admin.platform-saas-plans.create'],
                ],
                'admin.promo-codes' => [
                    ['label' => 'Create promo code', 'route' => 'admin.promo-codes.create'],
                ],
                'admin.announcements' => [
                    ['label' => 'New announcement', 'route' => 'admin.announcements.create'],
                ],
            ];
        }

        return array_merge($isp, $admin);
    }

    /**
     * @param  list<array{label: string, route: string, params?: array<string, mixed>, modal?: bool}>  $items
     * @return list<array{label: string, href: string, modal: bool}>
     */
    protected function presentItems(array $items): array
    {
        $presented = [];

        foreach ($items as $item) {
            $route = $item['route'] ?? null;

            if (! $route || ! Route::has($route)) {
                continue;
            }

            $href = route($route, $item['params'] ?? []);
            $modal = (bool) ($item['modal'] ?? $this->hrefSupportsModal($href));

            $presented[] = [
                'label' => $item['label'],
                'href' => $href,
                'modal' => $modal,
            ];
        }

        return $presented;
    }

    protected function hrefSupportsModal(string $href): bool
    {
        $path = parse_url($href, PHP_URL_PATH) ?? '';

        return (bool) preg_match('/\/(create|edit)(\/|$)/', $path);
    }
}
