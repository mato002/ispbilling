<?php

/**
 * FTP-only deploy helper: extract vendor.zip uploaded by GitHub Actions.
 *
 * Usage (after deploy): /deploy-extract-vendor.php?token=YOUR_TOKEN
 * Token is written to storage/app/.deploy-extract-token during CI deploy.
 *
 * Alternative without this URL: cPanel File Manager → select vendor.zip → Extract.
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

$zipPath = $root.'/vendor.zip';

if (! is_file($zipPath)) {
    http_response_code(404);
    exit("vendor.zip not found in the application root. Run deploy first or extract via cPanel File Manager.\n");
}

if (! class_exists(ZipArchive::class)) {
    http_response_code(500);
    exit("PHP ZipArchive extension is not enabled on this host.\n");
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    http_response_code(500);
    exit("Could not open vendor.zip.\n");
}

if (! $zip->extractTo($root)) {
    $zip->close();
    http_response_code(500);
    exit("Extract failed.\n");
}

$zip->close();
@unlink($zipPath);

echo "vendor/ extracted successfully.\n";
echo "You can remove vendor.zip from the server if it still appears in File Manager.\n";
