<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_payslips', 'pasi_base')) {
            Schema::table('payroll_payslips', function (Blueprint $table): void {
                $table->decimal('pasi_base', 12, 3)->nullable()->after('employer_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_payslips', 'pasi_base')) {
            Schema::table('payroll_payslips', function (Blueprint $table): void {
                $table->dropColumn('pasi_base');
            });
        }
    }
};
