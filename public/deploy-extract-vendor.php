<?php

/**
 * FTP-only deploy helper: merge vendor.part.* chunks and extract vendor/.
 *
 * After running the "Upload vendor (FTP chunks)" GitHub Action, open:
 *   /deploy-extract-vendor.php?token=YOUR_DEPLOY_EXTRACT_TOKEN
 *
 * Token file: storage/app/.deploy-extract-token (uploaded during deploy).
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$provided = (string) ($_GET['token'] ?? '');
$root = dirname(__DIR__);
$tokenFile = $root.'/storage/app/.deploy-extract-token';
$expected = is_readable($tokenFile) ? trim((string) file_get_contents($tokenFile)) : '';

if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    exit("Forbidden. Set DEPLOY_EXTRACT_TOKEN in GitHub secrets and re-run deploy.\n");
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
    exit("No vendor.zip or vendor.part.* files found. Run the Upload vendor (FTP chunks) workflow first.\n");
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
