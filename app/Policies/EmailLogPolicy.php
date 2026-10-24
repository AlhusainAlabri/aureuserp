<?php

declare(strict_types=1);

namespace App\Policies;

class EmailLogPolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'support_email::log';
    }
}
