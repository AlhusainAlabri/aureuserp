<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('navigation.menu_type', 'app_grid');
        $this->migrator->add('navigation.sidebar_default_state', 'auto');
        $this->migrator->add('navigation.menu_appearance', 'system');
    }
};
