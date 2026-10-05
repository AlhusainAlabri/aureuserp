<?php

namespace App\Support\Exports;

class Utf8Csv
{
    public const BOM = "\xEF\xBB\xBF";

    /**
     * Build a UTF-8 CSV string with BOM so Excel and similar tools open Arabic correctly.
     *
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    public static function fromRows(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv(
                $handle,
                array_map(
                    static fn (mixed $value): string => self::stringify($value),
                    $row,
                ),
                ',',
                '"',
                '\\',
            );
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return self::withBom($contents);
    }

    public static function withBom(string $contents): string
    {
        if (str_starts_with($contents, self::BOM)) {
            return $contents;
        }

        return self::BOM.$contents;
    }

    protected static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
