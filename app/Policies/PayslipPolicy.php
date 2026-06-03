<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Payroll\Models\Payslip;

class PayslipPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('view_any_payroll_payslip');
    }

    public function view(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('view_payroll_payslip');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_payroll_payslip');
    }

    public function update(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('update_payroll_payslip');
    }

    public function delete(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('delete_payroll_payslip');
    }

    public function deleteAny(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('delete_any_payroll_payslip');
    }

    public function recalculate(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('recalculate_payroll_payslip');
    }

    public function validate(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('validate_payroll_payslip');
    }

    public function exportPdf(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('export_pdf_payroll_payslip');
    }

    public function emailPdf(AuthUser $authUser, Payslip $payslip): bool
    {
        return $authUser->can('email_pdf_payroll_payslip');
    }
}
