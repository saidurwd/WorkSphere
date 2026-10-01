<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export — GAP-050.
 *
 * One implementation for every report, because "export" is the same problem
 * everywhere: turn rows into a downloadable file without loading more than the
 * export needs, and without letting a spreadsheet formula execute.
 *
 * FORMULA INJECTION: a cell beginning with `=`, `+`, `-` or `@` is interpreted as
 * a formula by Excel, Sheets and LibreOffice. A user-authored title such as
 * `=cmd|' /C calc'!A1` would therefore run when an administrator opened the
 * export. Every such cell is prefixed with a single quote, which the spreadsheet
 * shows as text and does not evaluate.
 *
 * Reports built on this class therefore export safely by default, and a report
 * that genuinely needs a leading `-` or `+` must opt out explicitly rather than
 * discovering the problem from an incident.
 */
class CsvExporter
{
    /**
     * Characters that make a spreadsheet treat the cell as a formula.
     */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * A CSV download response for a set of rows.
     *
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public function download(string $filename, array $headings, iterable $rows, ?string $from = null, ?string $to = null): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($headings, $rows): void {
                $handle = fopen('php://output', 'w');

                // A BOM so Excel opens UTF-8 correctly; without it accented and
                // non-Latin titles arrive mangled.
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, $headings);

                foreach ($rows as $row) {
                    fputcsv($handle, array_map($this->neutralise(...), array_values($row)));
                }

                fclose($handle);
            },
            $this->filename($filename, $from, $to),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Render to a string, for a test or a queued export.
     *
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public function render(array $headings, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headings);

        foreach ($rows as $row) {
            fputcsv($handle, array_map($this->neutralise(...), array_values($row)));
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return (string) $contents;
    }

    /**
     * Neutralise a cell that a spreadsheet would otherwise evaluate.
     */
    protected function neutralise(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $string = is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value;

        if ($string === '') {
            return '';
        }

        return in_array($string[0], self::FORMULA_PREFIXES, true)
            ? "'".$string
            : $string;
    }

    /**
     * A filename with the range in it, so two exports can be told apart.
     */
    protected function filename(string $base, ?string $from = null, ?string $to = null): string
    {
        $parts = array_filter([$base, $from, $to]);

        return implode('-', $parts).'.csv';
    }
}
