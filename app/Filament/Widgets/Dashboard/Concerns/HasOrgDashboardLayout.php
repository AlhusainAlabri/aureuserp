<?php

namespace App\Filament\Widgets\Dashboard\Concerns;

use App\Filament\Pages\Dashboard;

trait HasOrgDashboardLayout
{
    public function getColumnSpan(): int|string|array
    {
        if ($this->isOrgDashboardCommandCenterStat()) {
            return 12;
        }

        return [
            'default' => 12,
            'md'      => 6,
            'lg'      => 4,
        ];
    }

    protected function isOrgDashboardCommandCenterStat(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return in_array(static::class, Dashboard::getCommandCenterStatWidgets(), true);
    }
}
