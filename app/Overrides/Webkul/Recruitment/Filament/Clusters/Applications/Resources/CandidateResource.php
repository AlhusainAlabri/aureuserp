<?php

namespace Webkul\Recruitment\Filament\Clusters\Applications\Resources;

class CandidateResource extends CoreCandidateResource
{
    public static function getModelLabel(): string
    {
        return __('recruitments-extensions::labels.candidate.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('recruitments-extensions::labels.candidate.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('recruitments-extensions::labels.candidate.navigation');
    }
}
