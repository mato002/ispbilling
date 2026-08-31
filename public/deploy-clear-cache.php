<?php

/**
 * Delete stale bootstrap/cache/*.php (e.g. dev Pail provider) — FTP hosts without artisan.
 *
 *   /deploy-clear-cache.php?token=YOUR_TOKEN
 *
 * Token: DEPLOY_EXTRACT_TOKEN in .env (edit via cPanel File Manager) OR GitHub secret + deploy.
 */
declare(strict_types=1);

require __DIR__.'/deploy-token.php';

header('Content-Type: text/plain; charset=utf-8');

$provided = (string) ($_GET['token'] ?? '');
$root = dirname(__DIR__);
$expected = deploy_extract_expected_token($root);

if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
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
