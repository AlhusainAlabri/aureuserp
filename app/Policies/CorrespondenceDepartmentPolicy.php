<?php

declare(strict_types=1);

namespace App\Policies;

class CorrespondenceDepartmentPolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'correspondence_department';
    }
}
