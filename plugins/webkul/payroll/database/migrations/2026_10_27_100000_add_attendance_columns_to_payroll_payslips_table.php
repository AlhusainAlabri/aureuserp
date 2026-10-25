<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_payslips', function (Blueprint $table): void {
            if (! Schema::hasColumn('payroll_payslips', 'absent_days')) {
                $table->decimal('absent_days', 5, 1)->nullable()->after('unpaid_leave_days');
            }

            if (! Schema::hasColumn('payroll_payslips', 'late_minutes')) {
                $table->unsignedInteger('late_minutes')->nullable()->after('absent_days');
            }

            if (! Schema::hasColumn('payroll_payslips', 'overtime_hours')) {
                $table->decimal('overtime_hours', 8, 2)->nullable()->after('late_minutes');
            }
        });
    }

    public function down(): void
    {
        foreach (['overtime_hours', 'late_minutes', 'absent_days'] as $column) {
            if (Schema::hasColumn('payroll_payslips', $column)) {
                Schema::table('payroll_payslips', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
