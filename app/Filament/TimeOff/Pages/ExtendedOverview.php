<?php

namespace App\Filament\TimeOff\Pages;

use Webkul\TimeOff\Filament\Pages\Overview as BaseOverview;
use Webkul\TimeOff\Filament\Widgets\OverviewCalendarWidget as BaseOverviewCalendarWidget;

class ExtendedOverview extends BaseOverview
{
    public function getWidgets(): array
    {
        // Keep the base widget class so Livewire uses the kebab wire:name.
        // DashboardExtensionsServiceProvider swaps it to LocalizedOverviewCalendarWidget.
        return [
            BaseOverviewCalendarWidget::class,
        ];
    }
}
