<?php

namespace App\Services\Install;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the English reference data the installers seed (chart of accounts,
 * journals, units, stages…) with the names in lang/{locale}/seed-data.php.
 *
 * Only rows that still hold the seeded English value are changed, so running
 * it again, or after a customer renamed something, never overwrites their edits.
 */
class SeedDataLocalizer
{
    /**
     * Display-name columns that join the translated names of their rows, with the separator used.
     *
     * @var array<string, array{column: string, separator: string}>
     */
    protected array $pathColumns = [
        'inventories_locations' => ['column' => 'full_name', 'separator' => '/'],
        'products_categories'   => ['column' => 'full_name', 'separator' => ' / '],
    ];

    /**
     * @param  array<string, array<string, string>>|null  $dictionary  'table.column' => [English => translation]
     * @return array<string, int> rows updated per 'table.column'
     */
    public function localize(string $locale, ?array $dictionary = null): array
    {
        $dictionary ??= $this->dictionaryFor($locale);

        $updated = [];

        foreach ($dictionary as $target => $translations) {
            [$table, $column] = explode('.', $target, 2);

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $count = $this->replaceValues($table, $column, $translations);

            if ($count > 0) {
                $updated[$target] = $count;
            }
        }

        $this->rebuildPathColumns($dictionary, $updated);

        return $updated;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function dictionaryFor(string $locale): array
    {
        $path = lang_path("{$locale}/seed-data.php");

        if (! is_file($path)) {
            return [];
        }

        $dictionary = require $path;

        // Campaign titles mirror their names.
        if (isset($dictionary['utm_campaigns.name'])) {
            $dictionary['utm_campaigns.title'] = $dictionary['utm_campaigns.name'];
        }

        return $dictionary;
    }

    /**
     * @param  array<string, string>  $translations
     */
    protected function replaceValues(string $table, string $column, array $translations): int
    {
        $count = 0;

        foreach ($translations as $english => $translated) {
            try {
                $count += DB::table($table)->where($column, $english)->update([$column => $translated]);
            } catch (QueryException) {
                // A unique name already exists in this language; leave the English row as it is.
            }
        }

        return $count;
    }

    /**
     * @param  array<string, array<string, string>>  $dictionary
     * @param  array<string, int>  $updated
     */
    protected function rebuildPathColumns(array $dictionary, array &$updated): void
    {
        foreach ($this->pathColumns as $table => ['column' => $column, 'separator' => $separator]) {
            $names = $dictionary["{$table}.name"] ?? null;

            if ($names === null || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            foreach (DB::table($table)->select(['id', $column])->get() as $row) {
                $segments = explode($separator, (string) $row->{$column});
                $translated = implode($separator, array_map(fn (string $segment): string => $names[trim($segment)] ?? $segment, $segments));

                if ($translated !== $row->{$column}) {
                    DB::table($table)->where('id', $row->id)->update([$column => $translated]);
                    $updated["{$table}.{$column}"] = ($updated["{$table}.{$column}"] ?? 0) + 1;
                }
            }
        }
    }
}
