<?php

declare(strict_types=1);

require __DIR__.'/deploy-sync-lib.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @return array{exit_code: int, output: string, exception: ?string, files_total: int, applied_before: ?int, pending_before: ?int, applied_during: ?int, applied_after: ?int, pending_after: ?int, pending_names: list<string>, failed_migration: ?string, state_error: ?string}|string
 */
function deploy_run_migrations(string $laravelRoot): array|string
{
    if (! is_file($laravelRoot.'/vendor/autoload.php')) {
        return 'vendor/autoload.php not found. Upload/extract vendor/ on the server first.';
    }

    $files = array_values(array_filter(array_map(
        static fn (string $file): string => pathinfo($file, PATHINFO_FILENAME),
        glob($laravelRoot.'/database/migrations/*.php') ?: []
    )));
    sort($files);
    $before = ['ran' => null, 'error' => null];
    $after = ['ran' => null, 'error' => null];
    $exitCode = 1;
    $output = '';
    $exceptionMessage = null;

    try {
        require_once $laravelRoot.'/vendor/autoload.php';

        /** @var Application $app */
        $app = require $laravelRoot.'/bootstrap/app.php';
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();

        $before = deploy_migration_history_snapshot();

        try {
            $exitCode = $kernel->call('migrate', [
                '--force' => true,
                '--no-ansi' => true,
                '--no-interaction' => true,
            ]);
            $output = trim($kernel->output());
        } catch (\Throwable $exception) {
            $exceptionMessage = get_class($exception).': '.$exception->getMessage();
            $output = trim($kernel->output());
            $exitCode = 1;
        }
    } catch (\Throwable $exception) {
        $exceptionMessage = get_class($exception).': '.$exception->getMessage();
        $exitCode = 1;
    }

    $after = deploy_migration_history_snapshot();
    $ranBefore = $before['ran'];
    $ranAfter = $after['ran'];
    $pendingBefore = $ranBefore === null ? null : array_values(array_diff($files, $ranBefore));
    $pendingAfter = $ranAfter === null ? null : array_values(array_diff($files, $ranAfter));
    $appliedDuring = ($ranBefore === null || $ranAfter === null)
        ? null
        : count(array_diff($ranAfter, $ranBefore));
    $failedMigration = deploy_failed_migration_name($output);
    if ($failedMigration === null
        && $exitCode !== 0
        && $pendingAfter !== null
        && $pendingAfter !== []
        && (str_contains($output, 'SQLSTATE') || $exceptionMessage !== null)) {
        $failedMigration = $pendingAfter[0];
    }

    return [
        'exit_code' => $exitCode,
        'output' => $output,
        'exception' => $exceptionMessage,
        'files_total' => count($files),
        'applied_before' => $ranBefore === null ? null : count($ranBefore),
        'pending_before' => $pendingBefore === null ? null : count($pendingBefore),
        'applied_during' => $appliedDuring,
        'applied_after' => $ranAfter === null ? null : count($ranAfter),
        'pending_after' => $pendingAfter === null ? null : count($pendingAfter),
        'pending_names' => array_slice($pendingAfter ?? [], 0, 25),
        'failed_migration' => $failedMigration,
        'state_error' => $after['error'] ?? $before['error'],
    ];
}

/** @return array{ran: ?list<string>, error: ?string} */
function deploy_migration_history_snapshot(): array
{
    try {
        if (! Schema::hasTable('migrations')) {
            return ['ran' => [], 'error' => null];
        }

        $ran = DB::table('migrations')->orderBy('migration')->pluck('migration')
            ->map(static fn ($migration): string => (string) $migration)
            ->all();

        return ['ran' => $ran, 'error' => null];
    } catch (\Throwable $exception) {
        return ['ran' => null, 'error' => get_class($exception).': '.$exception->getMessage()];
    }
}

function deploy_failed_migration_name(string $output): ?string
{
    if (preg_match('/^\s*(\d{4}_\d{2}_\d{2}_\d{6}_[A-Za-z0-9_]+).*\bFAIL\b/m', $output, $matches)) {
        return $matches[1];
    }

    return null;
}

/** @param array<string, mixed> $result */
function deploy_migrate_report(array $result): string
{
    $count = static fn (string $key): string => isset($result[$key]) ? (string) $result[$key] : 'unknown';
    $lines = [
        'Migration run: '.(((int) ($result['exit_code'] ?? 1) === 0) ? 'SUCCESS' : 'FAILED'),
        'Migration files found: '.$count('files_total'),
        'Already recorded before run: '.$count('applied_before'),
        'Pending before run: '.$count('pending_before'),
        'New migrations recorded by this run: '.$count('applied_during'),
        'Recorded after run: '.$count('applied_after'),
        'Still pending after run: '.$count('pending_after'),
    ];

    if (! empty($result['failed_migration'])) {
        $lines[] = 'Failed migration: '.$result['failed_migration'];
    }
    if (! empty($result['state_error'])) {
        $lines[] = 'Migration history read error: '.$result['state_error'];
    }
    if (! empty($result['exception'])) {
        $lines[] = 'Migration exception: '.$result['exception'];
    }

    $pendingNames = $result['pending_names'] ?? [];
    if ($pendingNames !== []) {
        $lines[] = '';
        $lines[] = 'First pending migration files after run:';
        foreach ($pendingNames as $name) {
            $lines[] = '  - '.$name;
        }
        if (($result['pending_after'] ?? 0) > count($pendingNames)) {
            $lines[] = '  ... and '.($result['pending_after'] - count($pendingNames)).' more';
        }
    }

    if (($result['output'] ?? '') !== '') {
        $lines[] = '';
        $lines[] = 'Artisan output:';
        $lines[] = mb_substr((string) $result['output'], 0, 12000);
    }

    return implode("\n", $lines)."\n";
}
