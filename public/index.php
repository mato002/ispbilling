<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = null;

foreach ([
    dirname(__DIR__), // Standard: laravel-root/public/index.php
    dirname(__DIR__).'/ispbilling', // cPanel: public_html → sibling ispbilling/
    dirname(__DIR__, 2).'/ispbilling', // Legacy cPanel layout
] as $candidate) {
    if (is_file($candidate.'/vendor/autoload.php')) {
        $laravelRoot = $candidate;
        break;
    }
}

if ($laravelRoot === null) {
    http_response_code(500);
    exit('Laravel application root not found.');
}

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
