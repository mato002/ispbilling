<?php

/**
 * Delete stale bootstrap/cache/*.php (e.g. dev Pail provider) — FTP hosts without artisan.
 *
 *   POST /deploy-clear-cache.php with Authorization: Bearer YOUR_TOKEN
 *
 * Token: DEPLOY_EXTRACT_TOKEN in .env (edit via cPanel File Manager) OR GitHub secret + deploy.
 */
declare(strict_types=1);

require __DIR__.'/deploy-sync-lib.php';

header('Content-Type: text/plain; charset=utf-8');

$provided = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $provided = preg_replace('/^Bearer\s+/i', '', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
}
$root = deploy_resolve_laravel_root();
if ($root === null) {
    http_response_code(500);
    exit("Laravel application root not found. Expected a sibling /ispbilling directory.\n");
}
$expected = deploy_extract_expected_token($root);

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    exit(deploy_extract_forbidden_message());
}

$cacheDir = $root.'/bootstrap/cache';
$removed = [];

foreach (glob($cacheDir.'/*.php') ?: [] as $file) {
    if (@unlink($file)) {
        $removed[] = basename($file);
    }
}

if ($removed === []) {
    echo "No bootstrap cache files to remove (already clean).\n";
} else {
    echo 'Removed: '.implode(', ', $removed)."\n";
}

echo "Reload your site. If errors persist, upload vendor/ via File Manager (vendor.zip → Extract).\n";
