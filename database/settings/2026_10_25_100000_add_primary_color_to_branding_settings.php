<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('branding.primary_color')) {
            $this->migrator->add('branding.primary_color', null);
        }
    }
};
