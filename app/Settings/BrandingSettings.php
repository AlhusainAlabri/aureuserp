<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BrandingSettings extends Settings
{
    public ?string $display_name;

    public ?string $logo_full;

    public ?string $logo_full_dark;

    public ?string $logo_compact;

    public ?string $logo_compact_dark;

    public ?string $favicon;

    public ?string $primary_color = null;

    public static function group(): string
    {
        return 'branding';
    }
}
