<?php

namespace App\Filament\Concerns;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;

/**
 * Widgets that extend a plugin widget are not discovered by Shield, so it finds no permission for them
 * and falls back to a `canAccess()` that widgets do not have. Reuse the permission of the widget they extend.
 */
trait InheritsWidgetShieldPermission
{
    protected static function getWidgetPermission(): ?string
    {
        $widgets = FilamentShield::getWidgets();

        foreach ([static::class, ...array_values(class_parents(static::class))] as $class) {
            if (isset($widgets[$class])) {
                return array_key_first($widgets[$class]['permissions']);
            }
        }

        return null;
    }

    public static function canView(): bool
    {
        $permission = static::getWidgetPermission();
        $user = filament()->auth()?->user();

        return $permission && $user ? $user->can($permission) : true;
    }
}
