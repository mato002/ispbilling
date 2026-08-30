<?php

namespace App\Support;

/**
 * Presentation helpers for the platform security alerts portal.
 */
final class SecurityAlertPresenter
{
    /**
     * @return 'critical'|'warning'|'info'
     */
    public static function routerApiSeverity(?string $description): string
    {
        $text = strtolower((string) $description);

        if (
            str_contains($text, 'connection refused')
            || str_contains($text, 'socket')
            || str_contains($text, 'could not connect')
            || str_contains($text, 'connection reset')
            || str_contains($text, 'network is unreachable')
        ) {
            return 'critical';
        }

        if (
            str_contains($text, 'timeout')
            || str_contains($text, 'timed out')
            || str_contains($text, 'period of time')
        ) {
            return 'warning';
        }

        return 'info';
    }

    public static function formatLogDescription(?string $description): string
    {
        $escaped = e((string) $description);

        return (string) preg_replace(
            '/\b(\d{1,3}(?:\.\d{1,3}){3})\b/',
            '<code class="font-mono text-xs bg-slate-100 dark:bg-slate-800/80 px-1.5 py-0.5 rounded text-rose-600 dark:text-rose-400">$1</code>',
            $escaped
        );
    }
}
