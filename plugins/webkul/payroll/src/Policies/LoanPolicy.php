<?php

namespace Webkul\Payroll\Policies;

use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Models\Loan;
use Webkul\Security\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_loan');
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->can('view_payroll_loan');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_loan');
    }

    public function update(User $user, Loan $loan): bool
    {
        return $loan->status === LoanStatus::Draft
            && $user->can('update_payroll_loan');
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->can('delete_payroll_loan');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payroll_loan');
    }

    public function activate(User $user, Loan $loan): bool
    {
        return $loan->status === LoanStatus::Approved
            && $user->can('activate_payroll_loan');
    }

    public function cancel(User $user, Loan $loan): bool
    {
        return ! in_array($loan->status, [
            LoanStatus::Completed,
            LoanStatus::Cancelled,
        ], true) && $user->can('cancel_payroll_loan');
    }

    public function submitForApproval(User $user, Loan $loan): bool
    {
        return $loan->status === LoanStatus::Draft
            && $user->can('submit_for_approval_payroll_loan');
    }

    public function approve(User $user, Loan $loan): bool
    {
        return $user->can('approve_payroll_loan');
    }

    public function reject(User $user, Loan $loan): bool
    {
        return $user->can('reject_payroll_loan');
    }
}
