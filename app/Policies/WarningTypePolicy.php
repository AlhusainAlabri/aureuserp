<?php

declare(strict_types=1);

namespace App\Policies;

class WarningTypePolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'employee_warning::type';
    }
}
