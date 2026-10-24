<?php

declare(strict_types=1);

namespace App\Policies;

class AccountTagPolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'account_account::tag';
    }
}
