<?php

namespace App\Support\Audit;

use Illuminate\Database\Eloquent\Model;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\BankStatement;
use Webkul\Account\Models\BankStatementLine;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Tax;
use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Models\ImportBatch;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Payroll\Models\EmployeeComponent;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Webkul\Payroll\Models\GratuityTier;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\PayslipLine;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;

/**
 * The models whose changes are written to the audit log.
 *
 * Models that already log through Chatter's HasLogActivity are left out so a change never produces two entries.
 */
class AuditedModels
{
    /**
     * @return array<int, class-string<Model>>
     */
    public static function classes(): array
    {
        return array_values(array_filter(
            static::candidates(),
            fn (string $class): bool => class_exists($class)
                && ! in_array(HasLogActivity::class, class_uses_recursive($class), true),
        ));
    }

    /**
     * @return array<int, class-string<Model>>
     */
    protected static function candidates(): array
    {
        return [
            PayrollBatch::class,
            Payslip::class,
            PayslipLine::class,
            SalaryComponent::class,
            PasiRule::class,
            GratuityTier::class,
            EndOfServiceSettlement::class,
            EmployeeComponent::class,
            Loan::class,
            AttendanceDay::class,
            OvertimeRequest::class,
            ImportBatch::class,
            BankStatement::class,
            BankStatementLine::class,
            Journal::class,
            Account::class,
            Tax::class,
            User::class,
            Role::class,
        ];
    }
}
