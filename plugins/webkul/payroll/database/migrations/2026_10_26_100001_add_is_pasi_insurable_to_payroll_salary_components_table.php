<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Services\PasiDefaults;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_salary_components', 'is_pasi_insurable')) {
            Schema::table('payroll_salary_components', function (Blueprint $table): void {
                $table->boolean('is_pasi_insurable')->default(false)->after('is_taxable');
            });
        }

        PasiDefaults::convertSeededComponents();
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_salary_components', 'is_pasi_insurable')) {
            Schema::table('payroll_salary_components', function (Blueprint $table): void {
                $table->dropColumn('is_pasi_insurable');
            });
        }
    }
};
