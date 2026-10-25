<?php

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\PasiFixtures;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Filament\Resources\PasiRuleResource;
use Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages\CreatePasiRule;
use Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages\EditPasiRule;
use Webkul\Payroll\Filament\Resources\PasiRuleResource\Pages\ListPasiRules;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource\Pages\EditSalaryComponent;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Policies\PasiRulePolicy;
use Webkul\Security\Models\User;

const PASI_PERMISSIONS = ['view_any_payroll_pasi::rule', 'view_payroll_pasi::rule', 'create_payroll_pasi::rule', 'update_payroll_pasi::rule'];

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    PasiFixtures::clearRules();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function pasiAdmin(array $permissions = PASI_PERMISSIONS): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    foreach (PASI_PERMISSIONS as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    test()->actingAs($user);

    return $user;
}

it('PasiResourceTest: lists the rules for a permitted user', function (): void {
    $rule = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');
    pasiAdmin();

    Livewire::test(ListPasiRules::class)->assertSuccessful()->assertCanSeeTableRecords([$rule]);
});

it('PasiResourceTest: forbids the list without the view permission', function (): void {
    pasiAdmin([]);

    Livewire::test(ListPasiRules::class)->assertForbidden();
});

it('PasiResourceTest: a new rate closes the previous rule and starts the new one', function (): void {
    $old = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');
    pasiAdmin();

    Livewire::test(CreatePasiRule::class)
        ->fillForm([
            'category'       => PasiCategory::Omani->value,
            'employee_rate'  => 8,
            'employer_rate'  => 12,
            'salary_cap'     => 3000,
            'effective_from' => '2027-01-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($old->fresh()->effective_to->toDateString())->toBe('2026-12-31');

    $new = PasiRule::query()->where('category', 'omani')->whereNull('effective_to')->firstOrFail();
    expect((float) $new->employee_rate)->toBe(8.0)
        ->and((float) $new->salary_cap)->toBe(3000.0)
        ->and($new->effective_from->toDateString())->toBe('2027-01-01');
});

it('PasiResourceTest: rejects a new rate that does not start after the current one', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');
    pasiAdmin();

    Livewire::test(CreatePasiRule::class)
        ->fillForm([
            'category'       => PasiCategory::Omani->value,
            'employee_rate'  => 8,
            'employer_rate'  => 12,
            'effective_from' => '2025-06-01',
        ])
        ->call('create')
        ->assertHasFormErrors(['effective_from']);

    expect(PasiRule::query()->count())->toBe(1);
});

it('PasiResourceTest: validates the rate range', function (): void {
    pasiAdmin();

    Livewire::test(CreatePasiRule::class)
        ->fillForm([
            'category'       => PasiCategory::Omani->value,
            'employee_rate'  => 120,
            'employer_rate'  => -1,
            'effective_from' => '2027-01-01',
        ])
        ->call('create')
        ->assertHasFormErrors(['employee_rate', 'employer_rate']);
});

it('PasiResourceTest: edits a rate but rejects an edit into an overlap', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01', '2026-12-31');
    $current = PasiFixtures::rule(PasiCategory::Omani, 8, 12, null, '2027-01-01');
    pasiAdmin();

    Livewire::test(EditPasiRule::class, ['record' => $current->id])
        ->fillForm(['employee_rate' => 8.5])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $current->fresh()->employee_rate)->toBe(8.5);

    Livewire::test(EditPasiRule::class, ['record' => $current->id])
        ->fillForm(['effective_from' => '2026-06-01'])
        ->call('save')
        ->assertHasFormErrors(['effective_from']);

    expect($current->fresh()->effective_from->toDateString())->toBe('2027-01-01');
});

it('PasiResourceTest: forbids creating without the create permission', function (): void {
    pasiAdmin(['view_any_payroll_pasi::rule', 'view_payroll_pasi::rule']);

    Livewire::test(CreatePasiRule::class)->assertForbidden();
});

it('PasiResourceTest: policy permissions are the ones Shield generates for the resource', function (): void {
    expect(FilamentShield::getResourcePermissions(PasiRuleResource::class))->toContain(...PASI_PERMISSIONS);

    $user = pasiAdmin();
    $rule = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');
    $policy = new PasiRulePolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $rule))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $rule))->toBeTrue()
        ->and(method_exists($policy, 'delete'))->toBeFalse();
});

it('PasiResourceTest: the salary component form saves the insurable flag', function (): void {
    $components = PasiFixtures::components();
    pasiAdmin(['view_any_payroll_salary::component', 'view_payroll_salary::component', 'update_payroll_salary::component']);

    Livewire::test(EditSalaryComponent::class, ['record' => $components['basic']->id])
        ->fillForm(['is_pasi_insurable' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($components['basic']->fresh()->is_pasi_insurable)->toBeFalse();
});
