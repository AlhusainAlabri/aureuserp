<?php

namespace App\Filament\Traits;

use Filament\Actions\Action;
use Wezlo\FilamentApproval\Concerns\HasApprovalsResource;

trait HasApprovalActions
{
    use HasApprovalsResource;

    /**
     * @return array<Action>
     */
    public function getApprovalActions(): array
    {
        return $this->getApprovalHeaderActions();
    }
}
