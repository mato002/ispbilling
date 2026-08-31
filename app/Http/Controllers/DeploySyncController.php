<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DeploySyncController extends Controller
{
    public function syncPublic(Request $request): Response
    {
        $expected = (string) config('deploy.extract_token', '');

        if ($expected === '') {
            return $this->plainResponse(
                "DEPLOY_EXTRACT_TOKEN is not configured in .env.\n",
                403
            );
        }

        $provided = (string) $request->query('token', '');

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return $this->plainResponse("Forbidden.\n", 403);
        }

        $source = public_path();
        $destination = $this->resolvePublicWebRoot();

        if ($destination === null) {
            return $this->plainResponse(
                "Could not find document root. Set DEPLOY_PUBLIC_WEB_ROOT in .env (e.g. /home/user/public_html/ispbilling).\n",
                500
            );
        }

        if (realpath($source) === realpath($destination)) {
            return $this->plainResponse("Source and destination are the same — nothing to sync.\n");
        }

        $copied = [];
        $skipped = ['hot', 'storage'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $relative = $iterator->getSubPathName();
            $relative = str_replace('\\', '/', $relative);

            foreach ($skipped as $skip) {
                if ($relative === $skip || str_starts_with($relative, $skip.'/')) {
                    continue 2;
                }
            }

            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($item->isDir()) {
                if (! is_dir($target) && ! mkdir($target, 0755, true) && ! is_dir($target)) {
                    return $this->plainResponse("Failed to create directory: {$relative}\n", 500);
                }

                continue;
            }

            $targetDir = dirname($target);
            if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
                return $this->plainResponse("Failed to create directory for: {$relative}\n", 500);
            }

            if (@copy($item->getPathname(), $target)) {
                $copied[] = $relative;
            }
        }

        $cssCount = count(array_filter(
            $copied,
            static fn (string $path): bool => str_starts_with($path, 'build/assets/') && str_ends_with($path, '.css')
        ));

        $lines = [
            'Public sync complete.',
            'Source: '.$source,
            'Destination: '.$destination,
            'Files copied: '.count($copied),
            'CSS assets: '.$cssCount,
        ];

        if ($cssCount < 1) {
            return $this->plainResponse(implode("\n", $lines)."\n\nWarning: no CSS files in build/assets.\n", 500);
        }

        return $this->plainResponse(implode("\n", $lines)."\n");
    }

    private function resolvePublicWebRoot(): ?string
    {
        $configured = trim((string) config('deploy.public_web_root', ''));

        if ($configured !== '' && is_dir($configured)) {
            return rtrim($configured, '/\\');
        }

        $laravelRoot = base_path();

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

    private function plainResponse(string $body, int $status = 200): Response
    {
        return response($body, $status, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
