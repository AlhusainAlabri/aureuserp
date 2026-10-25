<?php

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\GratuityFixtures;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\DepartureReasonResource;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\DepartureReasonResource\Pages\ListDepartureReasons;
use Webkul\Employee\Models\DepartureReason;
use Webkul\Payroll\Filament\Pages\ManageGratuitySettings;
use Webkul\Payroll\Filament\Resources\GratuityTierResource;
use Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages\CreateGratuityTier;
use Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages\EditGratuityTier;
use Webkul\Payroll\Filament\Resources\GratuityTierResource\Pages\ListGratuityTiers;
use Webkul\Payroll\Models\GratuityTier;
use Webkul\Payroll\Policies\GratuityTierPolicy;
use Webkul\Payroll\Settings\GratuitySettings;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    GratuityFixtures::tiers();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * The permission names Shield generates for a resource, keyed by ability prefix.
 *
 * @return array<string, string>
 */
function gratuityResourcePermissions(string $resource): array
{
    $keys = FilamentShield::getResourcePermissions($resource);
    $suffix = Str::after(collect($keys)->first(fn (string $key): bool => str_starts_with($key, 'view_any_')), 'view_any_');

    return collect(['view_any', 'view', 'create', 'update'])
        ->mapWithKeys(fn (string $prefix): array => [$prefix => $prefix.'_'.$suffix])
        ->all();
}

function gratuityUser(array $permissions): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

it('GratuityResourcesTest: lists the tiers for a permitted user and forbids everyone else', function (): void {
    $tier = GratuityTier::query()->orderBy('from_year')->first();

    gratuityUser(gratuityResourcePermissions(GratuityTierResource::class));
    Livewire::test(ListGratuityTiers::class)->assertSuccessful()->assertCanSeeTableRecords([$tier]);

    gratuityUser([]);
    Livewire::test(ListGratuityTiers::class)->assertForbidden();
});

it('GratuityResourcesTest: creates a tier for a later period', function (): void {
    GratuityTier::query()->update(['effective_to' => '2029-12-31']);
    gratuityUser(gratuityResourcePermissions(GratuityTierResource::class));

    Livewire::test(CreateGratuityTier::class)
        ->fillForm(['from_year' => 0, 'to_year' => 5, 'days_per_year' => 20, 'effective_from' => '2030-01-01'])
        ->call('create')
        ->assertHasNoFormErrors();

    $tier = GratuityTier::query()->where('effective_from', '2030-01-01')->firstOrFail();

    expect((float) $tier->days_per_year)->toBe(20.0)->and((float) $tier->to_year)->toBe(5.0);
});

it('GratuityResourcesTest: shows a form error for a band that overlaps another in the same period', function (): void {
    gratuityUser(gratuityResourcePermissions(GratuityTierResource::class));

    Livewire::test(CreateGratuityTier::class)
        ->fillForm(['from_year' => 2, 'to_year' => 4, 'days_per_year' => 10, 'effective_from' => '2024-01-01'])
        ->call('create')
        ->assertHasFormErrors(['from_year']);

    expect(GratuityTier::query()->count())->toBe(2);
});

it('GratuityResourcesTest: edits a tier and keeps the overlap rule', function (): void {
    $first = GratuityTier::query()->where('from_year', 0)->firstOrFail();
    gratuityUser(gratuityResourcePermissions(GratuityTierResource::class));

    Livewire::test(EditGratuityTier::class, ['record' => $first->id])
        ->fillForm(['days_per_year' => 18])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $first->fresh()->days_per_year)->toBe(18.0);

    Livewire::test(EditGratuityTier::class, ['record' => $first->id])
        ->fillForm(['to_year' => 5])
        ->call('save')
        ->assertHasFormErrors(['from_year']);

    expect((float) $first->fresh()->to_year)->toBe(3.0);
});

it('GratuityResourcesTest: the tier policy uses the permissions Shield generates', function (): void {
    $permissions = gratuityResourcePermissions(GratuityTierResource::class);
    $user = gratuityUser(array_values($permissions));
    $tier = GratuityTier::query()->firstOrFail();
    $policy = new GratuityTierPolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $tier))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $tier))->toBeTrue()
        ->and(method_exists($policy, 'delete'))->toBeFalse();

    $stranger = gratuityUser([]);

    expect($policy->viewAny($stranger))->toBeFalse()->and($policy->update($stranger, $tier))->toBeFalse();
});

it('GratuityResourcesTest: the settings page saves and validates the values', function (): void {
    $page = collect(FilamentShield::getPages()[ManageGratuitySettings::class]['permissions'])->keys()->all();
    gratuityUser($page);

    expect(ManageGratuitySettings::canAccess())->toBeTrue();

    Livewire::test(ManageGratuitySettings::class)
        ->fillForm(['minimum_service_years' => 2, 'eligible_category' => 'omani', 'leave_payout_divisor' => 26])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(GratuitySettings::class);
    expect((float) $settings->minimum_service_years)->toBe(2.0)
        ->and($settings->eligible_category)->toBe('omani')
        ->and($settings->leave_payout_divisor)->toBe(26);

    Livewire::test(ManageGratuitySettings::class)
        ->fillForm(['leave_payout_divisor' => 0])
        ->call('save')
        ->assertHasFormErrors(['leave_payout_divisor']);
});

it('GratuityResourcesTest: the settings page is forbidden without its permission', function (): void {
    gratuityUser([]);

    expect(ManageGratuitySettings::canAccess())->toBeFalse();

    Livewire::test(ManageGratuitySettings::class)->assertForbidden();
});

it('GratuityResourcesTest: a departure reason can be marked as not paying gratuity', function (): void {
    $reason = DepartureReason::query()->create(['name' => 'Misconduct '.uniqid()]);
    gratuityUser(array_values(gratuityResourcePermissions(DepartureReasonResource::class)));

    Livewire::test(ListDepartureReasons::class)
        ->callAction(TestAction::make('edit')->table($reason), ['name' => $reason->name, 'pays_gratuity' => false])
        ->assertHasNoFormErrors();

    expect($reason->fresh()->pays_gratuity)->toBeFalse();
});
