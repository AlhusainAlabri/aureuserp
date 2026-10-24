<?php

namespace Webkul\Security\Filament\Resources;

class TeamResource extends CoreTeamResource
{
    public static function getModelLabel(): string
    {
        return static::translatedResourceLabel('singular') ?? __('security-extensions::labels.team.single');
    }

    public static function getPluralModelLabel(): string
    {
        return static::translatedResourceLabel('plural') ?? __('security-extensions::labels.team.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('security-extensions::labels.team.navigation');
    }
}
