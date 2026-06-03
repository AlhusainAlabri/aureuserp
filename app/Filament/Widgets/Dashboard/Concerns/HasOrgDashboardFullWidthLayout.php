<?php

namespace App\Filament\Widgets\Dashboard\Concerns;

trait HasOrgDashboardFullWidthLayout
{
    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 12,
            'lg'      => 12,
        ];
    }
}
