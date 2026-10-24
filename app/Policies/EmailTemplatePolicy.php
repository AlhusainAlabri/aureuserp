<?php

declare(strict_types=1);

namespace App\Policies;

class EmailTemplatePolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'support_email::template';
    }
}
