<?php

namespace App\Support\PluginManager;

class PluginManagerUi
{
    public static function isHidden(): bool
    {
        return (bool) config('plugin-manager.hide_ui', false);
    }
}
