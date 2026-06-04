<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class PermissionTables
{
    public static function areReady(): bool
    {
        return Schema::hasTable('permissions')
            && Schema::hasTable('roles');
    }
}
