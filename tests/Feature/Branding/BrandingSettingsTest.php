<?php

use App\Filament\Clusters\Settings\Pages\ManageBranding;
use App\Filament\Pages\ModuleLauncher;
use App\Settings\BrandingSettings;
use App\Support\Branding;
use App\Support\ExtensionPermissionsSync;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\Permission\Models\Role;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

function ensureBrandingSettings(): BrandingSettings
{
    try {
        $settings = app(BrandingSettings::class);
        $settings->display_name;
    } catch (MissingSettings) {
        Artisan::call('migrate', [
            '--path'           => 'database/settings/2026_10_11_200459_create_branding_settings.php',
            '--force'          => true,
            '--no-interaction' => true,
        ]);

        Artisan::call('settings:clear-cache');
        app()->forgetInstance(BrandingSettings::class);

        $settings = app(BrandingSettings::class);
    }

    $settings->display_name = null;
    $settings->logo_full = null;
    $settings->logo_full_dark = null;
    $settings->logo_compact = null;
    $settings->logo_compact_dark = null;
    $settings->favicon = null;
    $settings->save();

    Artisan::call('settings:clear-cache');

    return app(BrandingSettings::class);
}

beforeEach(function (): void {
    ensureBrandingSettings();
});

it('uses a custom display name and keeps an explicit company name', function (): void {
    $company = Company::factory()->create([
        'name'      => 'Company Legal Name',
        'is_active' => true,
        'parent_id' => null,
    ]);

    $user = User::factory()->create([
        'default_company_id' => $company->id,
    ]);

    $this->actingAs($user);

    $settings = app(BrandingSettings::class);
    $settings->display_name = 'Nodhum Brand';
    $settings->save();

    expect(brand_name())->toBe('Nodhum Brand')
        ->and(brand_name($company->id))->toBe('Company Legal Name');
});

it('falls back to the company name when the display name is empty', function (): void {
    $company = Company::factory()->create([
        'name'      => 'Fallback Company',
        'is_active' => true,
        'parent_id' => null,
    ]);

    $this->actingAs(User::factory()->create([
        'default_company_id' => $company->id,
    ]));

    expect(brand_name())->toBe('Fallback Company');
});

it('falls back to the bundled logos when no upload is saved', function (): void {
    expect(Branding::fullLogoUrl())->toBe(asset('images/logo_2.png'))
        ->and(Branding::fullLogoUrl(dark: true))->toBe(asset('images/logo_2.png'))
        ->and(Branding::compactLogoUrl())->toBe(asset('images/logo.png'))
        ->and(Branding::compactLogoUrl(dark: true))->toBe(asset('images/logo.png'))
        ->and(Branding::faviconUrl())->toBe(asset('images/favicon.ico'))
        ->and(Branding::fullLogoDataUri())->toStartWith('data:image/png;base64,');
});

it('uses uploaded logos and ignores unsafe paths', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('branding/full.png', 'full-logo');
    Storage::disk('public')->put('branding/full-dark.png', 'dark-logo');
    Storage::disk('public')->put('branding/compact.png', 'compact-logo');
    Storage::disk('public')->put('branding/favicon.png', 'favicon');

    $settings = app(BrandingSettings::class);
    $settings->logo_full = 'branding/full.png';
    $settings->logo_full_dark = 'branding/full-dark.png';
    $settings->logo_compact = 'branding/compact.png';
    $settings->favicon = 'branding/favicon.png';
    $settings->save();

    expect(Branding::fullLogoUrl())->toBe(Storage::disk('public')->url('branding/full.png'))
        ->and(Branding::fullLogoUrl(dark: true))->toBe(Storage::disk('public')->url('branding/full-dark.png'))
        ->and(Branding::compactLogoUrl())->toBe(Storage::disk('public')->url('branding/compact.png'))
        ->and(Branding::compactLogoUrl(dark: true))->toBe(Storage::disk('public')->url('branding/compact.png'))
        ->and(Branding::faviconUrl())->toBe(Storage::disk('public')->url('branding/favicon.png'));

    $dataUri = Branding::fullLogoDataUri();

    expect($dataUri)->toStartWith('data:image/png;base64,')
        ->and(base64_decode((string) str($dataUri)->after(',')))->toBe('full-logo');

    $settings->logo_full = '../.env';
    $settings->logo_full_dark = null;
    $settings->save();

    expect(Branding::fullLogoUrl())->toBe(asset('images/logo_2.png'));
});

it('uses the dark full logo for the compact slot when no compact logo is uploaded', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('branding/full-dark.png', 'dark-logo');

    $settings = app(BrandingSettings::class);
    $settings->logo_full_dark = 'branding/full-dark.png';
    $settings->save();

    expect(Branding::compactLogoUrl(dark: true))
        ->toBe(Storage::disk('public')->url('branding/full-dark.png'));
});

it('shows the saved full logo in the dashboard heading and module launcher', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('branding/full.png', 'full-logo');
    Storage::disk('public')->put('branding/full-dark.png', 'dark-logo');

    $settings = app(BrandingSettings::class);
    $settings->logo_full = 'branding/full.png';
    $settings->logo_full_dark = 'branding/full-dark.png';
    $settings->save();

    $user = User::factory()->create();
    $this->actingAs($user);

    expect(view('filament.components.org-dashboard-heading')->render())
        ->toContain(Storage::disk('public')->url('branding/full.png'))
        ->toContain('brand-logo--light')
        ->toContain('brand-logo--dark');

    Livewire::test(ModuleLauncher::class)
        ->assertSuccessful()
        ->assertSeeHtml(Storage::disk('public')->url('branding/full.png'))
        ->assertSeeHtml(Storage::disk('public')->url('branding/full-dark.png'));
});

it('points the admin and customer panels at the branding resolver', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('branding/full.png', 'full-logo');
    Storage::disk('public')->put('branding/compact.png', 'compact-logo');
    Storage::disk('public')->put('branding/favicon.png', 'favicon');

    $settings = app(BrandingSettings::class);
    $settings->logo_full = 'branding/full.png';
    $settings->logo_compact = 'branding/compact.png';
    $settings->favicon = 'branding/favicon.png';
    $settings->save();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    auth()->logout();

    expect(Filament::getCurrentPanel()->getBrandLogo())->toBe(Branding::fullLogoUrl())
        ->and(Filament::getCurrentPanel()->getDarkModeBrandLogo())->toBe(Branding::fullLogoUrl(dark: true))
        ->and(Filament::getCurrentPanel()->getFavicon())->toBe(Branding::faviconUrl());

    $this->actingAs(User::factory()->create());

    expect(Filament::getCurrentPanel()->getBrandLogo())->toBe(Branding::compactLogoUrl())
        ->and(Filament::getCurrentPanel()->getDarkModeBrandLogo())->toBe(Branding::compactLogoUrl(dark: true));

    Filament::setCurrentPanel(Filament::getPanel('customer'));

    expect(Filament::getCurrentPanel()->getBrandLogo())->toBe(Branding::fullLogoUrl())
        ->and(Filament::getCurrentPanel()->getFavicon())->toBe(Branding::faviconUrl());
});

it('allows access for users with the branding page permission', function (): void {
    app(ExtensionPermissionsSync::class)->syncBranding();

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_branding');

    $this->actingAs($user);

    expect(ManageBranding::canAccess())->toBeTrue();
});

it('denies access for users without the branding page permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(ManageBranding::canAccess())->toBeFalse();
});

it('grants the branding page to existing admin roles', function (): void {
    $role = Role::query()->firstOrCreate([
        'name'       => 'super_admin',
        'guard_name' => 'web',
    ]);

    app(ExtensionPermissionsSync::class)->syncBranding();

    expect($role->fresh()->hasPermissionTo('page_filament_manage_branding'))->toBeTrue();
});

it('renders the branding settings page for authorized users', function (): void {
    app(ExtensionPermissionsSync::class)->syncBranding();
    app()->setLocale('ar');

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_branding');

    $this->actingAs($user);

    Livewire::test(ManageBranding::class)
        ->assertSuccessful()
        ->assertSee(__('branding-settings.form.display_name.label'))
        ->assertSee(__('branding-settings.form.logo_full.label'));
});

it('persists the display name from the settings page without clearing logos', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('branding/keep.png', 'keep');
    app(ExtensionPermissionsSync::class)->syncBranding();

    $settings = app(BrandingSettings::class);
    $settings->logo_full = 'branding/keep.png';
    $settings->save();

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_branding');

    $this->actingAs($user);

    Livewire::test(ManageBranding::class)
        ->fillForm([
            'display_name' => 'Saved Brand',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    Artisan::call('settings:clear-cache');
    app()->forgetInstance(BrandingSettings::class);

    $reloaded = app(BrandingSettings::class);

    expect($reloaded->display_name)->toBe('Saved Brand')
        ->and($reloaded->logo_full)->toBe('branding/keep.png')
        ->and(brand_name())->toBe('Saved Brand')
        ->and(config('app.name'))->toBe('Saved Brand');

    Storage::disk('public')->assertExists('branding/keep.png');
});

it('stores an uploaded logo and deletes it when the field is cleared', function (): void {
    Storage::fake('public');
    app(ExtensionPermissionsSync::class)->syncBranding();

    $user = User::factory()->create();
    $user->givePermissionTo('page_filament_manage_branding');

    $this->actingAs($user);

    Livewire::test(ManageBranding::class)
        ->fillForm([
            'logo_full' => UploadedFile::fake()->image('new-full.png'),
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    Artisan::call('settings:clear-cache');
    app()->forgetInstance(BrandingSettings::class);

    $storedPath = app(BrandingSettings::class)->logo_full;

    expect($storedPath)->toStartWith('branding/')
        ->and($storedPath)->not->toBe('new-full.png');

    Storage::disk('public')->assertExists($storedPath);
    expect(Branding::fullLogoUrl())->toBe(Storage::disk('public')->url($storedPath));

    Livewire::test(ManageBranding::class)
        ->set('data.logo_full', null)
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    Artisan::call('settings:clear-cache');
    app()->forgetInstance(BrandingSettings::class);

    expect(app(BrandingSettings::class)->logo_full)->toBeNull();
    Storage::disk('public')->assertMissing($storedPath);
    expect(Branding::fullLogoUrl())->toBe(asset('images/logo_2.png'));
});
