<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Payroll\Models\Loan;

class LoanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('view_any_payroll_loan');
    }

    public function view(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('view_payroll_loan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_payroll_loan');
    }

    public function update(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('update_payroll_loan');
    }

    public function delete(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('delete_payroll_loan');
    }

    public function deleteAny(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('delete_any_payroll_loan');
    }

    public function activate(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('activate_payroll_loan');
    }

    public function cancel(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('cancel_payroll_loan');
    }

    public function submitForApproval(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('submit_for_approval_payroll_loan');
    }

    public function approve(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('approve_payroll_loan');
    }

    public function reject(AuthUser $authUser, Loan $loan): bool
    {
        return $authUser->can('reject_payroll_loan');
    }
}
