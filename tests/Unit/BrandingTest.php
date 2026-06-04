<?php

use App\Support\Branding;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    config(['branding.fallback' => 'Fallback ERP']);
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

it('does not include aureus in dashboard pdf footer translation', function (): void {
    $footer = __('dashboard.pdf.footer', ['brand' => 'Nodhum Org']);

    expect($footer)
        ->toContain('Nodhum Org')
        ->not->toContain('Aureus');
});
