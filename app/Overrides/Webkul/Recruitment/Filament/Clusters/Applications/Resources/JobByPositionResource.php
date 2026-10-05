<?php

namespace Webkul\Recruitment\Filament\Clusters\Applications\Resources;

class JobByPositionResource extends CoreJobByPositionResource
{
    public static function getModelLabel(): string
    {
        return __('recruitments-extensions::labels.job_by_position.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('recruitments-extensions::labels.job_by_position.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('recruitments-extensions::labels.job_by_position.navigation');
    }
}
