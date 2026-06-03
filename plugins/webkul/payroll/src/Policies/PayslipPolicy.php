<?php

namespace Webkul\Payroll\Policies;

use Webkul\Payroll\Models\Payslip;
use Webkul\Security\Models\User;

class PayslipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_payslip');
    }

    public function view(User $user, Payslip $payslip): bool
    {
        return $user->can('view_payroll_payslip');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_payslip');
    }

    public function update(User $user, Payslip $payslip): bool
    {
        return $payslip->isDraft() && $user->can('update_payroll_payslip');
    }

    public function delete(User $user, Payslip $payslip): bool
    {
        return $payslip->isDraft() && $user->can('delete_payroll_payslip');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payroll_payslip');
    }

    public function recalculate(User $user, Payslip $payslip): bool
    {
        return $payslip->isDraft() && $user->can('recalculate_payroll_payslip');
    }

    public function validate(User $user, Payslip $payslip): bool
    {
        return $payslip->isDraft() && $user->can('validate_payroll_payslip');
    }

    public function exportPdf(User $user, Payslip $payslip): bool
    {
        return $user->can('export_pdf_payroll_payslip');
    }

    public function emailPdf(User $user, Payslip $payslip): bool
    {
        return $user->can('email_pdf_payroll_payslip');
    }
}
