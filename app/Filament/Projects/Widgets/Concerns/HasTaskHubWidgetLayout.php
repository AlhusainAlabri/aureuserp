<?php

namespace App\Filament\Projects\Widgets\Concerns;

trait HasTaskHubWidgetLayout
{
    public function getColumnSpan(): int|string|array
    {
        return [
            'default' => 12,
            'lg'      => 6,
        ];
    }
}
