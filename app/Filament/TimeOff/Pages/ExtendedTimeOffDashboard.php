<?php

namespace App\Filament\TimeOff\Pages;

use App\Filament\Concerns\InteractsWithAdvancedDashboard;
use Webkul\TimeOff\Filament\Pages\Dashboard as BaseTimeOffDashboard;
use Webkul\TimeOff\Filament\Widgets\CalendarWidget;
use Webkul\TimeOff\Filament\Widgets\MyTimeOffWidget;

class ExtendedTimeOffDashboard extends BaseTimeOffDashboard
{
    use InteractsWithAdvancedDashboard;

    protected string $view = 'filament.pages.advanced-dashboard';

    public function getSubheading(): ?string
    {
        return __('dashboard.hub.time_off_description');
    }

    public function getWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            MyTimeOffWidget::make(),
        ];
    }
}
