<?php

namespace App\Filament\Recruitment\Widgets;

use App\Filament\Concerns\InheritsWidgetShieldPermission;
use Webkul\Recruitment\Filament\Widgets\JobPositionStatsWidget as BaseJobPositionStatsWidget;

class JobPositionStatsWidget extends BaseJobPositionStatsWidget
{
    use InheritsWidgetShieldPermission;

    protected ?string $pollingInterval = null;
}
