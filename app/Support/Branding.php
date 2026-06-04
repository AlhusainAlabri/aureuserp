<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Branding
{
    public static function displayName(?int $companyId = null): string
    {
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
}
