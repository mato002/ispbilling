<?php

declare(strict_types=1);

require __DIR__.'/deploy-token.php';

/**
 * @return array{copied: list<string>, source: string, destination: string, css_count: int}|string error message
 */
function deploy_sync_public_tree(string $laravelRoot): array|string
{
    $source = $laravelRoot.'/public';
    $destination = deploy_public_web_root($laravelRoot);

    if ($destination === null) {
        return 'Could not find document root. Set DEPLOY_PUBLIC_WEB_ROOT in .env (e.g. /home/user/public_html/ispbilling).';
    }

    if (! is_dir($source)) {
        return 'Laravel public folder not found: '.$source;
    }

    if (realpath($source) === realpath($destination)) {
        return [
            'copied' => [],
            'source' => $source,
            'destination' => $destination,
            'css_count' => 0,
        ];
    }

    $copied = [];
    $skipped = ['hot', 'storage'];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        /** @var SplFileInfo $item */
        $relative = str_replace('\\', '/', $iterator->getSubPathName());

        foreach ($skipped as $skip) {
            if ($relative === $skip || str_starts_with($relative, $skip.'/')) {
                continue 2;
            }
        }

        $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if ($item->isDir()) {
            if (! is_dir($target) && ! mkdir($target, 0755, true) && ! is_dir($target)) {
                return 'Failed to create directory: '.$relative;
            }

            continue;
        }

        $targetDir = dirname($target);
        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            return 'Failed to create directory for: '.$relative;
        }

        if (@copy($item->getPathname(), $target)) {
            $copied[] = $relative;
        }
    }

    $cssCount = count(array_filter(
        $copied,
        static fn (string $path): bool => str_starts_with($path, 'build/assets/') && str_ends_with($path, '.css')
    ));

    return [
        'copied' => $copied,
        'source' => $source,
        'destination' => $destination,
        'css_count' => $cssCount,
    ];
}

function deploy_resolve_laravel_root(): ?string
{
    foreach ([
        dirname(__DIR__),
        dirname(__DIR__, 2).'/ispbilling',
    ] as $candidate) {
        if (is_file($candidate.'/vendor/autoload.php')) {
            return $candidate;
        }
    }

    return null;
}

function deploy_public_web_root(string $laravelRoot): ?string
{
    $configured = trim(deploy_env_value($laravelRoot, 'DEPLOY_PUBLIC_WEB_ROOT'));

    if ($configured !== '' && is_dir($configured)) {
        return rtrim($configured, '/\\');
    }

    foreach ([
        dirname($laravelRoot).'/public_html/ispbilling',
        dirname($laravelRoot, 2).'/public_html/ispbilling',
    ] as $candidate) {
        if (is_dir($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function deploy_env_value(string $root, string $key): string
{
    $envFile = $root.'/.env';

    if (! is_readable($envFile)) {
        return '';
    }

    $prefix = $key.'=';

    foreach (file($envFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, $prefix)) {
            continue;
        }

        $value = trim(substr($line, strlen($prefix)));
        if (preg_match('/^"(.*)"$/s', $value, $matches)) {
            return $matches[1];
        }
        if (preg_match("/^'(.*)'$/s", $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    return '';
}

function deploy_sync_public_report(array $result): string
{
    if ($result['copied'] === [] && $result['css_count'] === 0) {
        return implode("\n", [
            'Public sync complete (already same folder or empty).',
            'Source: '.$result['source'],
            'Destination: '.$result['destination'],
        ])."\n";
    }

    return implode("\n", [
        'Public sync complete.',
        'Source: '.$result['source'],
        'Destination: '.$result['destination'],
        'Files copied: '.count($result['copied']),
        'CSS assets: '.$result['css_count'],
    ])."\n";
}
