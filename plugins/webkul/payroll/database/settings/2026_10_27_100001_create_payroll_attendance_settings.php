<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payroll_attendance.deduct_absence', false);
        $this->migrator->add('payroll_attendance.deduct_lateness', false);
        $this->migrator->add('payroll_attendance.pay_overtime', false);
    }

    public function down(): void
    {
        $this->migrator->delete('payroll_attendance.deduct_absence');
        $this->migrator->delete('payroll_attendance.deduct_lateness');
        $this->migrator->delete('payroll_attendance.pay_overtime');
    }
};
