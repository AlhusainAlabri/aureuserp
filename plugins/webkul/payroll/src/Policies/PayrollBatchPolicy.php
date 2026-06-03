<?php

namespace Webkul\Payroll\Policies;

use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Security\Models\User;

class PayrollBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_payroll::batch');
    }

    public function view(User $user, PayrollBatch $payrollBatch): bool
    {
        return $user->can('view_payroll_payroll::batch');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_payroll::batch');
    }

    public function update(User $user, PayrollBatch $payrollBatch): bool
    {
        return $payrollBatch->isDraft() && $user->can('update_payroll_payroll::batch');
    }

    public function delete(User $user, PayrollBatch $payrollBatch): bool
    {
        return $user->can('delete_payroll_payroll::batch');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payroll_payroll::batch');
    }

    public function generate(User $user, PayrollBatch $payrollBatch): bool
    {
        return $payrollBatch->isDraft() && $user->can('generate_payroll_payroll::batch');
    }

    public function markPaid(User $user, PayrollBatch $payrollBatch): bool
    {
        return $payrollBatch->canBePaid() && $user->can('mark_paid_payroll_payroll::batch');
    }

    public function postToAccounting(User $user, PayrollBatch $payrollBatch): bool
    {
        return $payrollBatch->canBePosted() && $user->can('post_to_accounting_payroll_payroll::batch');
    }

    public function exportWps(User $user, PayrollBatch $payrollBatch): bool
    {
        return $payrollBatch->isFullyApproved() && $user->can('export_wps_payroll_payroll::batch');
    }

    public function exportPdf(User $user, PayrollBatch $payrollBatch): bool
    {
        return $user->can('export_pdf_payroll_payroll::batch');
    }

    public function submitForApproval(User $user, PayrollBatch $payrollBatch): bool
    {
        return $payrollBatch->isDraft() && $user->can('submit_for_approval_payroll_payroll::batch');
    }

    public function approve(User $user, PayrollBatch $payrollBatch): bool
    {
        return $user->can('approve_payroll_payroll::batch');
    }

    public function reject(User $user, PayrollBatch $payrollBatch): bool
    {
        return $user->can('reject_payroll_payroll::batch');
    }
}
