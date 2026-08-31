<?php

declare(strict_types=1);

require __DIR__.'/deploy-sync-lib.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * @return array{exit_code: int, output: string}|string
 */
function deploy_run_migrations(string $laravelRoot): array|string
{
    if (! is_file($laravelRoot.'/vendor/autoload.php')) {
        return 'vendor/autoload.php not found. Upload/extract vendor/ on the server first.';
    }

    require $laravelRoot.'/vendor/autoload.php';

    /** @var Application $app */
    $app = require $laravelRoot.'/bootstrap/app.php';

    /** @var Kernel $kernel */
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    $exitCode = $kernel->call('migrate', ['--force' => true]);
    $output = trim($kernel->output());

    return [
        'exit_code' => $exitCode,
        'output' => $output,
    ];
}

function deploy_migrate_report(array $result): string
{
    $lines = [
        'Migrations complete.',
        'Exit code: '.$result['exit_code'],
    ];

    if ($result['output'] !== '') {
        $lines[] = '';
        $lines[] = $result['output'];
    }

    return implode("\n", $lines)."\n";
}
