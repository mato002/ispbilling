<?php

namespace App\Support\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Small dependency-free XLSX writer for filtered tabular exports. */
class XlsxExporter
{
    /** @param list<string> $headers @param iterable<array<int, scalar|null>> $rows */
    public static function download(string $filename, array $headers, iterable $rows, string $sheetName = 'Report'): StreamedResponse
    {
        $sheetName = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $sheetName) ?: 'Report';
        $sheetName = mb_substr($sheetName, 0, 31);
        $xmlRows = '<row r="1">'.self::cells($headers, 1).'</row>';
        $rowNumber = 2;
        foreach ($rows as $row) {
            if ($rowNumber > 1048576) break;
            $xmlRows .= '<row r="'.$rowNumber.'">'.self::cells($row, $rowNumber).'</row>';
            $rowNumber++;
        }

        $entries = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xmlRows.'</sheetData></worksheet>',
        ];
        $xlsx = self::zip($entries);

        return response()->streamDownload(static function () use ($xlsx) {
            echo $xlsx;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Build a small standards-compliant ZIP container without filesystem temp files. */
    private static function zip(array $entries): string
    {
        $body = '';
        $directory = '';
        foreach ($entries as $name => $contents) {
            $compressed = gzdeflate($contents);
            $crc = crc32($contents);
            $nameLength = strlen($name);
            $offset = strlen($body);
            $body .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, 0, 0, $crc, strlen($compressed), strlen($contents), $nameLength, 0).$name.$compressed;
            $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, 0, 0, $crc, strlen($compressed), strlen($contents), $nameLength, 0, 0, 0, 0, 0, $offset).$name;
        }
        $directoryOffset = strlen($body);
        $entryCount = count($entries);
        $body .= $directory.pack('VvvvvVVv', 0x06054b50, 0, 0, $entryCount, $entryCount, strlen($directory), $directoryOffset, 0);

        return $body;
    }

    private static function cells(array $values, int $rowNumber): string
    {
        $xml = '';
        foreach (array_values($values) as $index => $value) {
            $column = '';
            $n = $index + 1;
            while ($n > 0) {
                $rem = ($n - 1) % 26;
                $column = chr(65 + $rem).$column;
                $n = intdiv($n - 1, 26);
            }
            $ref = $column.$rowNumber;
            if (is_int($value) || is_float($value) || (is_string($value) && $value !== '' && is_numeric($value))) {
                $xml .= '<c r="'.$ref.'"><v>'.htmlspecialchars((string) $value, ENT_XML1, 'UTF-8').'</v></c>';
            } else {
                $text = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
            }
        }

        return $xml;
    }
}
