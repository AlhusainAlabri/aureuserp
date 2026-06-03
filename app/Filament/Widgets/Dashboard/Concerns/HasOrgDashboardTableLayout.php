<?php

namespace App\Filament\Widgets\Dashboard\Concerns;

trait HasOrgDashboardTableLayout
{
    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 12,
            'lg'      => 6,
        ];
    }
}
