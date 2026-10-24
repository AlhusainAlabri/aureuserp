<?php

namespace Webkul\Recruitment\Filament\Clusters\Applications\Resources;

class ApplicantResource extends CoreApplicantResource
{
    public static function getModelLabel(): string
    {
        return static::translatedResourceLabel('singular') ?? __('recruitments-extensions::labels.applicant.single');
    }

    public static function getPluralModelLabel(): string
    {
        return static::translatedResourceLabel('plural') ?? __('recruitments-extensions::labels.applicant.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('recruitments-extensions::labels.applicant.navigation');
    }
}
