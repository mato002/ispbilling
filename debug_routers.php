<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ROUTER DEBUG ===\n";
$routers = App\Models\Router::all(['id', 'name', 'ip_address', 'isp_id']);
foreach ($routers as $router) {
    echo "ID: {$router->id} - {$router->name} - {$router->ip_address} (ISP: {$router->isp_id})\n";
}

echo "\n=== ISP DEBUG ===\n";
$isps = App\Models\ISP::all(['id', 'name']);
foreach ($isps as $isp) {
    echo "ID: {$isp->id} - {$isp->name}\n";
}

echo "\n=== PLAN DEBUG ===\n";
$plans = App\Models\Plan::where('is_active', true)->get(['id', 'name', 'isp_id']);
foreach ($plans as $plan) {
    echo "ID: {$plan->id} - {$plan->name} (ISP: {$plan->isp_id})\n";
}

echo "\n=== TESTING ROUTER LOOKUP ===\n";
$routerIp = '192.168.1.1';
$router = App\Models\Router::where('ip_address', $routerIp)->first();
if ($router) {
    echo "Found router: {$router->name}\n";
    $isp = $router->isp;
    if ($isp) {
        echo "ISP: {$isp->name}\n";
        $plans = App\Models\Plan::where('isp_id', $isp->id)->where('is_active', true)->get();
        echo "Plans found: " . $plans->count() . "\n";
    }
} else {
    echo "Router with IP {$routerIp} NOT found\n";
    echo "Available router IPs:\n";
    foreach ($routers as $r) {
        echo "- {$r->ip_address}\n";
    }
}
