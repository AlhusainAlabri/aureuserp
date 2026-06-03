<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Payroll\Models\SalaryComponent;

class SalaryComponentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, SalaryComponent $salaryComponent): bool
    {
        return $authUser->can('view_any_payroll_salary::component');
    }

    public function view(AuthUser $authUser, SalaryComponent $salaryComponent): bool
    {
        return $authUser->can('view_payroll_salary::component');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_payroll_salary::component');
    }

    public function update(AuthUser $authUser, SalaryComponent $salaryComponent): bool
    {
        return $authUser->can('update_payroll_salary::component');
    }

    public function delete(AuthUser $authUser, SalaryComponent $salaryComponent): bool
    {
        return $authUser->can('delete_payroll_salary::component');
    }

    public function deleteAny(AuthUser $authUser, SalaryComponent $salaryComponent): bool
    {
        return $authUser->can('delete_any_payroll_salary::component');
    }
}
