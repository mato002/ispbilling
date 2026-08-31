<?php

declare(strict_types=1);

/**
 * Copy Laravel public/ to cPanel document root (public_html/ispbilling).
 *
 *   /deploy-sync-public.php?token=YOUR_TOKEN
 */
require __DIR__.'/deploy-sync-lib.php';

header('Content-Type: text/plain; charset=utf-8');

$provided = (string) ($_GET['token'] ?? '');
$laravelRoot = deploy_resolve_laravel_root();

if ($laravelRoot === null) {
    http_response_code(500);
    exit("Laravel application root not found.\n");
}

$expected = deploy_extract_expected_token($laravelRoot);

if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    exit(deploy_extract_forbidden_message());
}

$result = deploy_sync_public_tree($laravelRoot);

if (is_string($result)) {
    http_response_code(500);
    exit($result."\n");
}

if ($result['css_count'] < 1 && $result['copied'] !== []) {
    http_response_code(500);
    exit(deploy_sync_public_report($result)."\nWarning: no CSS files in build/assets.\n");
}

echo deploy_sync_public_report($result);
