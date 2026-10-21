<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('branding.display_name', null);
        $this->migrator->add('branding.logo_full', null);
        $this->migrator->add('branding.logo_full_dark', null);
        $this->migrator->add('branding.logo_compact', null);
        $this->migrator->add('branding.logo_compact_dark', null);
        $this->migrator->add('branding.favicon', null);
    }
};
