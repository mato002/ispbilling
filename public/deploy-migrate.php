<?php

declare(strict_types=1);

/**
 * Run pending Laravel migrations on production (no SSH required).
 *
 *   POST /deploy-migrate.php with Authorization: Bearer YOUR_TOKEN
 *
 * This public helper locates the sibling Laravel application directory.
 */
require __DIR__.'/deploy-migrate-lib.php';

header('Content-Type: text/plain; charset=utf-8');

$provided = '';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $provided = preg_replace('/^Bearer\s+/i', '', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
}
$laravelRoot = deploy_resolve_laravel_root();

if ($laravelRoot === null) {
    http_response_code(500);
    exit("Laravel application root not found.\n");
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
    exit("Resolved Laravel root: {$laravelRoot}\nThe initial migration source is missing or stale; refusing to run migrations.\n");
}

echo "Resolved Laravel root: {$laravelRoot}\n";

// Shared-hosting OPcache may keep migration files from the previous deploy.
// Reset it after authorization and before booting Laravel so retries use the
// updated, partial-migration-safe migration code.
if (function_exists('opcache_reset')) {
    @opcache_reset();
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
