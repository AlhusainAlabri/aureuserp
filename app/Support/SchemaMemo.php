<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class SchemaMemo
{
    /** @var array<string, bool> */
    protected static array $tables = [];

    /** @var array<string, bool> */
    protected static array $columns = [];

    public static function hasTable(string $table): bool
    {
        if (array_key_exists($table, static::$tables)) {
            return static::$tables[$table];
        }

        return static::$tables[$table] = Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (array_key_exists($key, static::$columns)) {
            return static::$columns[$key];
        }

        if (! static::hasTable($table)) {
            return static::$columns[$key] = false;
        }

        return static::$columns[$key] = Schema::hasColumn($table, $column);
    }

    public static function resetForTesting(): void
    {
        static::$tables = [];
        static::$columns = [];
    }
}
