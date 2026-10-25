<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees_departure_reasons') || Schema::hasColumn('employees_departure_reasons', 'pays_gratuity')) {
            return;
        }

        Schema::table('employees_departure_reasons', function (Blueprint $table): void {
            $table->boolean('pays_gratuity')->default(true);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('employees_departure_reasons') && Schema::hasColumn('employees_departure_reasons', 'pays_gratuity')) {
            Schema::table('employees_departure_reasons', function (Blueprint $table): void {
                $table->dropColumn('pays_gratuity');
            });
        }
    }
};
