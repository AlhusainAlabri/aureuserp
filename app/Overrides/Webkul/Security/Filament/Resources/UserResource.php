<?php

namespace Webkul\Security\Filament\Resources;

class UserResource extends CoreUserResource
{
    public static function getModelLabel(): string
    {
        return __('security-extensions::labels.user.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('security-extensions::labels.user.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('security-extensions::labels.user.navigation');
    }
}
