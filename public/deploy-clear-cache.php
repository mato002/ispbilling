<?php

/**
 * Delete stale bootstrap/cache/*.php (e.g. dev Pail provider) — FTP hosts without artisan.
 *
 *   /deploy-clear-cache.php?token=YOUR_DEPLOY_EXTRACT_TOKEN
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$provided = (string) ($_GET['token'] ?? '');
$root = dirname(__DIR__);
$tokenFile = $root.'/storage/app/.deploy-extract-token';
$expected = is_readable($tokenFile) ? trim((string) file_get_contents($tokenFile)) : '';

if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    exit("Forbidden.\n");
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

echo "Reload your site. If errors persist, upload vendor/ (see deploy-extract-vendor.php).\n";
