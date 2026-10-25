<?php

namespace Webkul\Payroll\Services;

use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Models\Payslip;

class PasiCalculator
{
    /**
     * Insurable earnings of the payslip, capped at the rule's salary cap.
     */
    public function base(Payslip $payslip, PasiRule $rule): float
    {
        $insurable = (float) $payslip->lines()
            ->where('type', SalaryComponentType::Earning->value)
            ->whereHas('component', fn ($query) => $query->where('is_pasi_insurable', true))
            ->sum('amount');

        if ($rule->salary_cap !== null) {
            $insurable = min($insurable, (float) $rule->salary_cap);
        }

        return round($insurable, 3);
    }

    public function employeeShare(Payslip $payslip, PasiRule $rule): float
    {
        return round($this->base($payslip, $rule) * (float) $rule->employee_rate / 100, 3);
    }

    public function employerShare(Payslip $payslip, PasiRule $rule): float
    {
        return round($this->base($payslip, $rule) * (float) $rule->employer_rate / 100, 3);
    }
}
