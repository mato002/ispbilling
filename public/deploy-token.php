<?php

declare(strict_types=1);

/**
 * Resolve deploy helper token from storage file or .env (for FTP-only hosts).
 */
function deploy_extract_expected_token(string $root): string
{
    $tokenFile = $root.'/storage/app/.deploy-extract-token';
    if (is_readable($tokenFile)) {
        $fromFile = trim((string) file_get_contents($tokenFile));
        if ($fromFile !== '') {
            return $fromFile;
        }
    }

    $envFile = $root.'/.env';
    if (! is_readable($envFile)) {
        return '';
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        if (! str_starts_with($line, 'DEPLOY_EXTRACT_TOKEN=')) {
            continue;
        }
        $value = trim(substr($line, strlen('DEPLOY_EXTRACT_TOKEN=')));
        if (preg_match('/^"(.*)"$/s', $value, $m)) {
            return $m[1];
        }
        if (preg_match("/^'(.*)'$/s", $value, $m)) {
            return $m[1];
        }

        return $value;
    }

    return '';
}

function deploy_extract_forbidden_message(): string
{
    return "Forbidden.\n\n"
        ."Set a token using ONE of these (no terminal needed):\n"
        ."  1. cPanel File Manager → edit .env → add:\n"
        ."     DEPLOY_EXTRACT_TOKEN=choose-any-long-secret\n"
        ."  2. GitHub → Settings → Secrets → DEPLOY_EXTRACT_TOKEN → re-run Deploy workflow\n\n"
        ."Or fix cache manually: delete all .php files in bootstrap/cache/ via File Manager.\n";
}
