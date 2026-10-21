<?php

namespace App\Support;

use App\Settings\BrandingSettings;
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

        $cacheKey = $companyId !== null
            ? "branding.company.{$companyId}"
            : 'branding.default.'.(Auth::id() ?? 'guest');

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
                ['images/logo_2.png', 'images/logo-full-dark.svg', 'images/logo-full-light.svg', 'images/logo.png', 'images/logo.svg'],
            );
        }

        return self::resolvePublicUrl(
            ['logo_full'],
            ['images/logo_2.png', 'images/logo-full-light.svg', 'images/logo.png', 'images/logo.svg'],
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

        if (is_file(public_path('images/logo.png'))) {
            return asset('images/logo.png');
        }

        return self::fullLogoUrl($dark);
    }

    public static function faviconUrl(): string
    {
        $uploaded = self::uploadedPublicUrl('favicon');

        if ($uploaded !== null) {
            return $uploaded;
        }

        if (is_file(public_path('images/favicon.ico'))) {
            return asset('images/favicon.ico');
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
        if (! self::canUseCache()) {
            return;
        }

        if ($companyId !== null) {
            Cache::forget("branding.company.{$companyId}");
        }

        Cache::forget('branding.default.'.(Auth::id() ?? 'guest'));
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

        return asset('images/logo_2.png');
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

        foreach (['images/logo_2.png', 'images/logo-full-light.svg', 'images/logo.png', 'images/logo.svg'] as $publicRelativePath) {
            $absolutePath = public_path($publicRelativePath);

            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    }
}
