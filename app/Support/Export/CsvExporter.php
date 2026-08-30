<?php

namespace App\Support\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * @param  list<string>  $headers
     */
    public static function download(string $filename, array $headers, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writer) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            $writer($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
