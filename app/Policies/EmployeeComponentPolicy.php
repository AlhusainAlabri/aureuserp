<?php

declare(strict_types=1);

namespace App\Policies;

class EmployeeComponentPolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'payroll_employee::component';
    }
}
