<?php

namespace App\Support;

use App\Settings\BrandingSettings;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Throwable;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Branding
{
    /**
     * Per-request memo of the primary palette (null = no custom color), so the panel
     * resolves it once however many times Filament asks for its colours.
     *
     * @var array{palette: array<int|string, string>|null}|null
     */
    protected static ?array $paletteMemo = null;

    /**
     * Per-request memo of the saved colour (also "no colour").
     *
     * @var array{hex: string|null}|null
     */
    protected static ?array $colorMemo = null;

    /**
     * Lowest contrast against white that still keeps white button text readable.
     */
    public const MIN_CONTRAST_WITH_WHITE = 3.0;

    /**
     * The site's primary colour as "#rrggbb", or null to keep the default.
     */
    public static function primaryColor(): ?string
    {
        if (self::$colorMemo !== null) {
            return self::$colorMemo['hex'];
        }

        try {
            $color = self::settings()?->primary_color;
        } catch (Throwable) {
            $color = null;
        }

        self::$colorMemo = ['hex' => self::normalizeHex($color)];

        return self::$colorMemo['hex'];
    }

    /**
     * Tiny stylesheet that makes the module icons follow a custom primary colour. Empty on
     * sites that keep the default colour, so the bundled icons keep their own green.
     */
    public static function iconColorStyle(): string
    {
        if (self::primaryColor() === null) {
            return '';
        }

        return '<style>:root{--brand-icon:var(--primary-600);--brand-icon-soft:var(--primary-200)}</style>';
    }

    /**
     * Filament palette (50–950) generated from the site's primary colour. The palette is
     * cached by colour, so changing the colour needs no cache clearing.
     *
     * @return array<int|string, string>|null
     */
    public static function primaryPalette(): ?array
    {
        if (self::$paletteMemo !== null) {
            return self::$paletteMemo['palette'];
        }

        $hex = self::primaryColor();

        $palette = match (true) {
            $hex === null          => null,
            self::canUseCache()    => Cache::rememberForever("branding.palette.{$hex}", fn (): array => Color::hex($hex)),
            default                => Color::hex($hex),
        };

        self::$paletteMemo = ['palette' => $palette];

        return $palette;
    }

    public static function normalizeHex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        if (preg_match('/^#([0-9a-f]{3})$/', $value, $short) === 1) {
            $value = '#'.preg_replace('/(.)/', '$1$1', $short[1]);
        }

        return preg_match('/^#[0-9a-f]{6}$/', $value) === 1 ? $value : null;
    }

    /**
     * WCAG contrast ratio between the colour and white (1 to 21).
     */
    public static function contrastWithWhite(string $hex): float
    {
        $hex = self::normalizeHex($hex) ?? '#ffffff';

        $channels = array_map(function (string $part): float {
            $value = hexdec($part) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));

        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

        return round(1.05 / ($luminance + 0.05), 2);
    }

    public static function displayName(?int $companyId = null): string
    {
        if ($companyId === null) {
            $customName = self::customDisplayName();

            if ($customName !== null) {
                return $customName;
            }
        }

        if (! self::canUseCache()) {
            return self::resolveDisplayName($companyId);
        }

        $cacheKey = self::cacheVersion().'.'.($companyId !== null
            ? "branding.company.{$companyId}"
            : 'branding.default.'.(Auth::id() ?? 'guest'));

        return Cache::remember($cacheKey, 3600, fn (): string => self::resolveDisplayName($companyId));
    }

    protected static function resolveDisplayName(?int $companyId): string
    {
        if ($companyId !== null) {
            $name = self::companyName($companyId);

            if ($name !== null) {
                return $name;
            }
        }

        $user = Auth::user();

        if ($user instanceof User && $user->default_company_id) {
            $name = self::companyName((int) $user->default_company_id);

            if ($name !== null) {
                return $name;
            }
        }

        $defaultCompanyName = self::defaultCompanyName();

        if ($defaultCompanyName !== null) {
            return $defaultCompanyName;
        }

        return (string) config('branding.fallback', config('app.name', 'Laravel'));
    }

    public static function fullLogoUrl(bool $dark = false): string
    {
        if ($dark) {
            return self::resolvePublicUrl(
                ['logo_full_dark', 'logo_full'],
                [self::defaultLogoPath()],
            );
        }

        return self::resolvePublicUrl(
            ['logo_full'],
            [self::defaultLogoPath()],
        );
    }

    public static function compactLogoUrl(bool $dark = false): string
    {
        $uploaded = $dark
            ? self::uploadedPublicUrl('logo_compact_dark')
                ?? self::uploadedPublicUrl('logo_compact')
                ?? self::uploadedPublicUrl('logo_full_dark')
                ?? self::uploadedPublicUrl('logo_full')
            : self::uploadedPublicUrl('logo_compact')
                ?? self::uploadedPublicUrl('logo_full');

        if ($uploaded !== null) {
            return $uploaded;
        }

        $compactPath = (string) config('branding.default_logo_compact');

        if ($compactPath !== '' && is_file(public_path($compactPath))) {
            return asset($compactPath);
        }

        return self::fullLogoUrl($dark);
    }

    public static function faviconUrl(): string
    {
        $uploaded = self::uploadedPublicUrl('favicon');

        if ($uploaded !== null) {
            return $uploaded;
        }

        $bundled = (string) config('branding.default_favicon');

        if ($bundled !== '' && is_file(public_path($bundled))) {
            return asset($bundled);
        }

        return asset('favicon.ico');
    }

    public static function fullLogoDataUri(): ?string
    {
        $path = self::fullLogoAbsolutePath();

        if ($path === null) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'svg'         => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'ico'         => 'image/x-icon',
            default       => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    public static function forgetCache(?int $companyId = null): void
    {
        self::$paletteMemo = null;
        self::$colorMemo = null;

        if (! self::canUseCache()) {
            return;
        }

        Cache::forever('branding.version', self::cacheVersion() + 1);
    }

    protected static function cacheVersion(): int
    {
        return (int) Cache::get('branding.version', 1);
    }

    protected static function companyName(int $companyId): ?string
    {
        if (! self::hasCompaniesTable()) {
            return null;
        }

        $name = Company::query()->whereKey($companyId)->value('name');

        return filled($name) ? (string) $name : null;
    }

    protected static function defaultCompanyName(): ?string
    {
        if (! self::hasCompaniesTable()) {
            return null;
        }

        $name = Company::query()
            ->active()
            ->parents()
            ->orderBy('sort')
            ->value('name');

        return filled($name) ? (string) $name : null;
    }

    protected static function hasCompaniesTable(): bool
    {
        return Schema::hasTable('companies');
    }

    protected static function canUseCache(): bool
    {
        if (! self::cacheStoreUsesDatabase()) {
            return true;
        }

        return Schema::hasTable('cache');
    }

    protected static function cacheStoreUsesDatabase(): bool
    {
        $store = config('cache.default');
        $driver = config("cache.stores.{$store}.driver");

        return $driver === 'database';
    }

    protected static function customDisplayName(): ?string
    {
        $name = self::settings()?->display_name;

        if (! is_string($name)) {
            return null;
        }

        $name = trim($name);

        return $name === '' ? null : $name;
    }

    protected static function settings(): ?BrandingSettings
    {
        try {
            if (! Schema::hasTable('settings')) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        try {
            $settings = app(BrandingSettings::class);
            $settings->display_name;

            return $settings;
        } catch (MissingSettings) {
            return null;
        }
    }

    /**
     * @param  list<string>  $settingKeys
     * @param  list<string>  $publicRelativePaths
     */
    protected static function resolvePublicUrl(array $settingKeys, array $publicRelativePaths): string
    {
        foreach ($settingKeys as $settingKey) {
            $uploaded = self::uploadedPublicUrl($settingKey);

            if ($uploaded !== null) {
                return $uploaded;
            }
        }

        foreach ($publicRelativePaths as $relativePath) {
            if (is_file(public_path($relativePath))) {
                return asset($relativePath);
            }
        }

        return asset(self::defaultLogoPath());
    }

    /**
     * Bundled logo shown until an administrator uploads one. Sites can point it
     * at their own file with BRANDING_DEFAULT_LOGO.
     */
    protected static function defaultLogoPath(): string
    {
        return (string) config('branding.default_logo');
    }

    protected static function uploadedPublicUrl(string $settingKey): ?string
    {
        $relativePath = self::uploadedRelativePath($settingKey);

        if ($relativePath === null || ! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        return Storage::disk('public')->url($relativePath);
    }

    protected static function uploadedRelativePath(string $settingKey): ?string
    {
        $path = self::settings()?->{$settingKey} ?? null;

        if (! is_string($path) || blank($path)) {
            return null;
        }

        $path = str_replace('\\', '/', $path);

        if (str_contains($path, '..') || ! str_starts_with($path, 'branding/')) {
            return null;
        }

        return $path;
    }

    protected static function fullLogoAbsolutePath(): ?string
    {
        $relativePath = self::uploadedRelativePath('logo_full');

        if ($relativePath !== null) {
            $absolutePath = Storage::disk('public')->path($relativePath);

            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        foreach ([self::defaultLogoPath()] as $publicRelativePath) {
            $absolutePath = public_path($publicRelativePath);

            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    }
}
