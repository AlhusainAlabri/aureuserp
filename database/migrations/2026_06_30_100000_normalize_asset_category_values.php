<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets')) {
            return;
        }

        DB::table('assets')->where('category', 'vehicles')->update(['category' => 'vehicle']);
        DB::table('assets')->where('category', 'electronics')->update(['category' => 'equipment']);
        DB::table('assets')->whereNotIn('category', ['vehicle', 'furniture', 'equipment'])->update(['category' => null]);
    }

    public function down(): void
    {
        // No rollback — legacy values were invalid for the enum.
    }
};
