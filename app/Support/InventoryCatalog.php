<?php

namespace App\Support;

class InventoryCatalog
{
    public const CATEGORIES = [
        'router' => 'Router',
        'ap' => 'Access Point',
        'cpe' => 'CPE Device',
        'switch' => 'Switch',
        'radio' => 'Network Radio',
    ];

    public const STATUSES = [
        'available' => 'Available',
        'assigned' => 'Assigned',
        'faulty' => 'Faulty',
        'retired' => 'Retired',
    ];

    public const LOCATIONS = [
        'warehouse' => 'Warehouse',
        'pop_site' => 'POP Site',
        'customer' => 'Customer',
        'technician' => 'Technician',
    ];

    public const INSTALLATION_STATUSES = [
        'pending' => 'Pending',
        'completed' => 'Completed',
    ];

    public const TRANSACTION_ACTIONS = [
        'added' => 'Added',
        'assigned' => 'Assigned',
        'returned' => 'Returned',
        'replaced' => 'Replaced',
        'faulty' => 'Marked faulty',
        'retired' => 'Retired',
        'technician_assigned' => 'Issued to technician',
        'technician_returned' => 'Returned from technician',
        'installed' => 'Installed at customer',
    ];

    public const BRANDS = [
        'MikroTik',
        'TP-Link',
        'Ubiquiti',
        'Cambium',
        'Mimosa',
        'Other',
    ];

    public static function categoryLabel(?string $category): string
    {
        return self::CATEGORIES[$category] ?? ucfirst((string) $category);
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[$status] ?? ucfirst((string) $status);
    }

    public static function locationLabel(?string $location): string
    {
        return self::LOCATIONS[$location] ?? ucfirst((string) $location);
    }

    public static function actionLabel(?string $action): string
    {
        return self::TRANSACTION_ACTIONS[$action] ?? ucfirst((string) $action);
    }
}
