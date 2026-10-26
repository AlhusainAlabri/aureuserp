<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payslip lines that payroll writes itself (lateness, overtime, loan instalments) have no salary component, so
 * HR had nowhere to give them an accounting account. These inactive components carry that mapping only: inactive
 * components cannot be assigned to employees and never produce lines on their own.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_salary_components')) {
            return;
        }

        $rows = [
            ['code' => 'LATE', 'name' => 'Lateness deduction', 'name_ar' => 'خصم التأخير', 'type' => 'deduction', 'sort_order' => 920],
            ['code' => 'OVERTIME', 'name' => 'Overtime pay', 'name_ar' => 'أجر العمل الإضافي', 'type' => 'earning', 'sort_order' => 150],
            ['code' => 'LOAN', 'name' => 'Loan instalment', 'name_ar' => 'قسط القرض', 'type' => 'deduction', 'sort_order' => 900],
        ];

        foreach ($rows as $row) {
            if (DB::table('payroll_salary_components')->where('code', $row['code'])->exists()) {
                continue;
            }

            DB::table('payroll_salary_components')->insert($row + [
                'calculation_type'  => 'fixed',
                'is_taxable'        => false,
                'is_pasi_insurable' => false,
                'is_active'         => false,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Left in place: they may already carry account mappings.
    }
};
