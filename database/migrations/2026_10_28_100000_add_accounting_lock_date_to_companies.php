<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'accounting_lock_date')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->date('accounting_lock_date')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'accounting_lock_date')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('accounting_lock_date');
        });
    }
};
