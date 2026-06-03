<?php

namespace Webkul\Payroll\Policies;

use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Security\Models\User;

class SalaryComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_salary::component');
    }

    public function view(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->can('view_payroll_salary::component');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_salary::component');
    }

    public function update(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->can('update_payroll_salary::component');
    }

    public function delete(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->can('delete_payroll_salary::component');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payroll_salary::component');
    }
}
