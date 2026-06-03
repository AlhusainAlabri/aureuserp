<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Payroll\Models\PayrollBatch;

class PayrollBatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('view_any_payroll_payroll::batch');
    }

    public function view(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('view_payroll_payroll::batch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_payroll_payroll::batch');
    }

    public function update(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('update_payroll_payroll::batch');
    }

    public function delete(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('delete_payroll_payroll::batch');
    }

    public function deleteAny(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('delete_any_payroll_payroll::batch');
    }

    public function generate(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('generate_payroll_payroll::batch');
    }

    public function markPaid(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('mark_paid_payroll_payroll::batch');
    }

    public function postToAccounting(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('post_to_accounting_payroll_payroll::batch');
    }

    public function exportWps(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('export_wps_payroll_payroll::batch');
    }

    public function exportPdf(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('export_pdf_payroll_payroll::batch');
    }

    public function submitForApproval(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('submit_for_approval_payroll_payroll::batch');
    }

    public function approve(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('approve_payroll_payroll::batch');
    }

    public function reject(AuthUser $authUser, PayrollBatch $payrollBatch): bool
    {
        return $authUser->can('reject_payroll_payroll::batch');
    }
}
