<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$manifest = $root.'/build/manifest.json';
$assetsDir = $root.'/build/assets';

echo "Document root: {$root}\n\n";

echo is_file($manifest) ? "OK  build/manifest.json exists\n" : "MISSING  build/manifest.json\n";

if (is_dir($assetsDir)) {
    $files = glob($assetsDir.'/*') ?: [];
    echo 'OK  build/assets/ has '.count($files)." file(s)\n";
    foreach (array_slice($files, 0, 8) as $file) {
        echo '  - '.basename($file).' ('.round(filesize($file) / 1024).' KB)'."\n";
    }
} else {
    echo "MISSING  build/assets/ folder\n";
}

echo "\nFix: redeploy from GitHub, or upload public/build/ from your PC via File Manager.\n";
