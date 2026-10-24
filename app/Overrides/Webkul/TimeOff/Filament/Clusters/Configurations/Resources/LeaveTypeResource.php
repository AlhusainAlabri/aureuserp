<?php

namespace Webkul\TimeOff\Filament\Clusters\Configurations\Resources;

class LeaveTypeResource extends CoreLeaveTypeResource
{
    public static function getModelLabel(): string
    {
        return static::translatedResourceLabel('singular') ?? __('time-off-extensions::labels.leave_type.single');
    }

    public static function getPluralModelLabel(): string
    {
        return static::translatedResourceLabel('plural') ?? __('time-off-extensions::labels.leave_type.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('time-off-extensions::labels.leave_type.navigation');
    }
}
