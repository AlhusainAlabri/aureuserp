<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class PermissionTables
{
    protected static ?bool $ready = null;

    public static function areReady(): bool
    {
        return static::$ready ??= Schema::hasTable('permissions')
            && Schema::hasTable('roles');
    }

    public static function resetForTesting(): void
    {
        static::$ready = null;
    }
}
