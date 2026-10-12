<?php

/**
 * FTP-only deploy helper: merge vendor.part.* chunks and extract vendor/.
 *
 *   POST /deploy-extract-vendor.php with Authorization: Bearer YOUR_TOKEN
 *
 * Token: DEPLOY_EXTRACT_TOKEN in .env OR GitHub secret + deploy.
 */
declare(strict_types=1);

require __DIR__.'/deploy-token.php';

header('Content-Type: text/plain; charset=utf-8');

$provided = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $provided = preg_replace('/^Bearer\s+/i', '', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
}
$root = dirname(__DIR__);
$expected = deploy_extract_expected_token($root);

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    exit(deploy_extract_forbidden_message());
}

if (! class_exists(ZipArchive::class)) {
    http_response_code(500);
    exit("PHP ZipArchive extension is not enabled on this host.\n");
}

$zipPath = $root.'/vendor.zip';
$parts = glob($root.'/vendor.part.*') ?: [];

if ($parts !== []) {
    sort($parts, SORT_NATURAL);
    $out = fopen($zipPath, 'wb');
    if ($out === false) {
        http_response_code(500);
        exit("Could not create vendor.zip for merge.\n");
    }

    foreach ($parts as $part) {
        $in = fopen($part, 'rb');
        if ($in === false) {
            fclose($out);
            @unlink($zipPath);
            http_response_code(500);
            exit("Could not read {$part}.\n");
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
    }

    fclose($out);

    foreach ($parts as $part) {
        @unlink($part);
    }

    @unlink($root.'/vendor.manifest.json');
    echo 'Merged '.count($parts)." chunk(s) into vendor.zip.\n";
} elseif (! is_file($zipPath)) {
    http_response_code(404);
    exit("No vendor.zip or vendor.part.* on server.\nUpload vendor.zip via cPanel File Manager and Extract instead.\n");
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    http_response_code(500);
    exit("Could not open vendor.zip after merge.\n");
}

if (! $zip->extractTo($root)) {
    $zip->close();
    http_response_code(500);
    exit("Extract failed.\n");
}

$zip->close();
@unlink($zipPath);

echo "vendor/ extracted successfully.\n";
