<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources;

class JobPositionResource extends CoreJobPositionResource
{
    public static function getModelLabel(): string
    {
        return __('recruitments-extensions::labels.job_position.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('recruitments-extensions::labels.job_position.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('recruitments-extensions::labels.job_position.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('recruitments-extensions::labels.job_position.group');
    }
}
