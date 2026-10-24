<?php

use App\Settings\BrandingSettings;
use App\Support\Branding;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    config(['branding.fallback' => 'Fallback ERP']);
    Cache::flush();

    try {
        $settings = app(BrandingSettings::class);

        if (filled($settings->display_name)) {
            $settings->display_name = null;
            $settings->save();
        }
    } catch (MissingSettings) {
        // Branding settings are optional until the settings migration has run.
    }
});

it('returns company name when company id is provided', function (): void {
    $company = Company::factory()->create([
        'name'       => 'Nodhum Org',
        'is_active'  => true,
        'parent_id'  => null,
    ]);

    expect(Branding::displayName($company->id))->toBe('Nodhum Org');
});

it('returns authenticated user default company name', function (): void {
    $company = Company::factory()->create([
        'name'       => 'User Default Co',
        'is_active'  => true,
        'parent_id'  => null,
    ]);

    $user = User::factory()->create([
        'default_company_id' => $company->id,
    ]);

    $this->actingAs($user);

    expect(Branding::displayName())->toBe('User Default Co');
});

it('returns first active parent company when no user context', function (): void {
    Company::query()->delete();

    Company::factory()->create([
        'name'      => 'Primary Co',
        'is_active' => true,
        'parent_id' => null,
        'sort'      => 1,
    ]);

    expect(Branding::displayName())->toBe('Primary Co');
});

it('returns branding fallback when no company exists', function (): void {
    Company::query()->delete();

    expect(Branding::displayName())->toBe('Fallback ERP');
});

it('returns branding without cache table during fresh install', function (): void {
    config([
        'cache.default'     => 'database',
        'branding.fallback' => 'Install Fallback',
    ]);

    Schema::partialMock()
        ->shouldReceive('hasTable')
        ->with('cache')
        ->andReturn(false)
        ->shouldReceive('hasTable')
        ->with('companies')
        ->andReturn(false);

    expect(Branding::displayName())->toBe('Install Fallback');
});

it('shows the neutral bundled logo when no full logo is uploaded', function (): void {
    clearUploadedLogos();

    expect(Branding::fullLogoUrl())->toEndWith('/images/brand/default-logo.svg')
        ->and(Branding::fullLogoUrl(dark: true))->toEndWith('/images/brand/default-logo.svg')
        ->and(Branding::compactLogoUrl())->toEndWith('/images/brand/default-mark.svg')
        ->and(is_file(public_path('images/logo-full-light.svg')))->toBeFalse()
        ->and(is_file(public_path('images/logo-full-dark.svg')))->toBeFalse();
});

it('lets a site point the bundled logo at its own png wordmark', function (): void {
    clearUploadedLogos();

    config([
        'branding.default_logo'         => 'images/logo_2.png',
        'branding.default_logo_compact' => 'images/logo.png',
    ]);

    expect(Branding::fullLogoUrl())->toEndWith('/images/logo_2.png')
        ->and(Branding::fullLogoUrl(dark: true))->toEndWith('/images/logo_2.png')
        ->and(Branding::compactLogoUrl())->toEndWith('/images/logo.png');
});

it('refreshes the cached display name when the company is renamed', function (): void {
    Company::query()->delete();

    $company = Company::factory()->create([
        'name'      => 'Before Rename Co',
        'is_active' => true,
        'parent_id' => null,
        'sort'      => 1,
    ]);

    expect(Branding::displayName())->toBe('Before Rename Co');

    $company->update(['name' => 'After Rename Co']);

    expect(Branding::displayName())->toBe('After Rename Co');
});

function clearUploadedLogos(): void
{
    try {
        $settings = app(BrandingSettings::class);
        $settings->logo_full = null;
        $settings->logo_full_dark = null;
        $settings->logo_compact = null;
        $settings->logo_compact_dark = null;
        $settings->save();
    } catch (MissingSettings) {
        // Branding settings are optional until the settings migration has run.
    }
}

it('does not include aureus in dashboard pdf footer translation', function (): void {
    $footer = __('dashboard.pdf.footer', ['brand' => 'Nodhum Org']);

    expect($footer)
        ->toContain('Nodhum Org')
        ->not->toContain('Aureus');
});
