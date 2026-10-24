<?php

declare(strict_types=1);

namespace App\Policies;

class ApprovalFlowPolicy extends ShieldPolicy
{
    protected function permission(): string
    {
        return 'filament_approval_approval::flow';
    }
}
