<?php

declare(strict_types=1);

/**
 * Versioned migration endpoint for cPanel layouts where the Laravel app is a
 * sibling of public_html. The new filename avoids stale helper bytecode.
 */
require __DIR__.'/deploy-migrate-lib.php';

header('Content-Type: text/plain; charset=utf-8');

$provided = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $provided = preg_replace('/^Bearer\s+/i', '', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
}

$laravelRoot = dirname(__DIR__).'/ispbilling';

if (! is_file($laravelRoot.'/vendor/autoload.php')) {
    http_response_code(500);
    exit("Laravel application not found at expected sibling path: {$laravelRoot}\n");
}

$expected = deploy_extract_expected_token($laravelRoot);

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
    http_response_code(403);
    exit(deploy_extract_forbidden_message());
}

$initialMigration = $laravelRoot.'/database/migrations/0001_01_01_000000_create_users_table.php';
$initialMigrationSource = @file_get_contents($initialMigration);

if (! is_string($initialMigrationSource)
    || ! str_contains($initialMigrationSource, "string('email', 191)")
    || ! str_contains($initialMigrationSource, "string('id', 191)")) {
    http_response_code(409);
    exit("Laravel root: {$laravelRoot}\nThe initial migration source is missing or stale; refusing to run migrations.\n");
}

echo "Laravel root: {$laravelRoot}\n";

if (function_exists('opcache_reset')) {
    @opcache_reset();
}

if (function_exists('opcache_invalidate')) {
    foreach (glob($laravelRoot.'/database/migrations/*.php') ?: [] as $migrationFile) {
        clearstatcache(true, $migrationFile);
        @opcache_invalidate($migrationFile, true);
    }
}

$result = deploy_run_migrations($laravelRoot);

if (is_string($result)) {
    http_response_code(500);
    exit($result."\n");
}

if ($result['exit_code'] !== 0) {
    http_response_code(500);
    exit(deploy_migrate_report($result));
}

echo deploy_migrate_report($result);
