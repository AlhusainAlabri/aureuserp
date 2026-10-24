<?php

declare(strict_types=1);

namespace App\Policies;

class EmployeeSubmissionPolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'employee_submission';
    }
}
