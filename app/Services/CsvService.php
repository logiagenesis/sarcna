<?php
declare(strict_types=1);

namespace App\Services;

/** CSV exports for the committee — orders, manifests, applications, donations. */
final class CsvService
{
    public static function build(array $rows, array $columns = []): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        if ($columns === [] && $rows !== []) {
            $columns = array_combine(array_keys($rows[0]), array_map(
                static fn (string $key): string => ucwords(str_replace('_', ' ', $key)),
                array_keys($rows[0])
            ));
        }

        // BOM so Excel opens UTF-8 correctly.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_values($columns));

        foreach ($rows as $row) {
            $line = [];

            foreach (array_keys($columns) as $key) {
                $value = $row[$key] ?? '';

                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                $line[] = self::sanitise((string) $value);
            }

            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /** Neutralise spreadsheet formula injection from user-supplied text. */
    private static function sanitise(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'" . $value
            : $value;
    }

    public static function filename(string $name): string
    {
        return sprintf('sarcna-2027-%s-%s.csv', slugify($name), date('Y-m-d-Hi'));
    }
}
