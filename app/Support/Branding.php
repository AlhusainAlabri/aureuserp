<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class Branding
{
    public static function displayName(?int $companyId = null): string
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
}
