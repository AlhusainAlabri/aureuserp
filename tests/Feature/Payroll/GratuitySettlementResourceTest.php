<?php

use App\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use App\Services\Hr\HrExtensionSchemaService;
use App\Support\ExtensionPermissionsSync;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\GratuityFixtures;
use Tests\Helpers\PasiFixtures;
use Webkul\Employee\Filament\Resources\EmployeeResource;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Filament\Pages\ManageGratuitySettings;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages\EditEndOfServiceSettlement;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages\ListEndOfServiceSettlements;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages\ViewEndOfServiceSettlement;
use Webkul\Payroll\Filament\Resources\GratuityTierResource;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Webkul\Payroll\Policies\EndOfServiceSettlementPolicy;
use Webkul\Payroll\Services\FinalSettlementBuilder;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    app(HrExtensionSchemaService::class)->ensure();
    GratuityFixtures::tiers();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ApprovalFlow::query()->where('approvable_type', (new EndOfServiceSettlement)->getMorphClass())->each(function (ApprovalFlow $flow): void {
        $flow->steps()->delete();
        $flow->delete();
    });
});

/**
 * @return array<string, string> ability prefix => permission name, as Shield generates them for the resource
 */
function srPermissions(string $resource): array
{
    $keys = FilamentShield::getResourcePermissions($resource);
    $suffix = Str::after(collect($keys)->first(fn (string $key): bool => str_starts_with($key, 'view_any_')), 'view_any_');

    return collect(['view_any', 'view', 'create', 'update'])
        ->mapWithKeys(fn (string $prefix): array => [$prefix => $prefix.'_'.$suffix])
        ->all();
}

function srUser(array $permissions): User
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

function srSettlement(string $nationality = 'IN'): EndOfServiceSettlement
{
    $employee = GratuityFixtures::employee(PasiFixtures::company('OM'), $nationality, '2020-01-01', '2023-01-16');

    return app(FinalSettlementBuilder::class)->build($employee);
}

function srFlow(User $approver): ApprovalFlow
{
    $flow = ApprovalFlow::query()->create([
        'name'            => 'Settlement resource flow',
        'approvable_type' => (new EndOfServiceSettlement)->getMorphClass(),
        'is_active'       => true,
    ]);

    $flow->steps()->create([
        'name'            => 'Step 1', 'order' => 1, 'type' => 'single', 'approver_resolver' => UserResolver::class,
        'approver_config' => ['user_ids' => [$approver->id]], 'required_approvals' => 1,
    ]);

    return $flow->fresh('steps');
}

it('GratuitySettlementResourceTest: lists settlements for a permitted user and forbids everyone else', function (): void {
    $settlement = srSettlement();

    srUser(array_values(srPermissions(EndOfServiceSettlementResource::class)));
    Livewire::test(ListEndOfServiceSettlements::class)->assertSuccessful()->assertCanSeeTableRecords([$settlement]);

    srUser([]);
    Livewire::test(ListEndOfServiceSettlements::class)->assertForbidden();
});

it('GratuitySettlementResourceTest: the employee page creates a draft settlement for a departed employee', function (): void {
    $employee = GratuityFixtures::employee(PasiFixtures::company('OM'), 'IN', '2020-01-01', '2023-01-16');
    srUser([
        ...array_values(srPermissions(EndOfServiceSettlementResource::class)),
        ...array_values(srPermissions(EmployeeResource::class)),
    ]);

    Livewire::test(ViewEmployee::class, ['record' => $employee->id])
        ->callAction('createEndOfServiceSettlement')
        ->assertRedirect();

    $settlement = EndOfServiceSettlement::query()->where('employee_id', $employee->id)->firstOrFail();

    expect($settlement->status)->toBe(SettlementStatus::Draft);

    Livewire::test(ViewEmployee::class, ['record' => $employee->id])
        ->assertActionHidden('createEndOfServiceSettlement');
});

it('GratuitySettlementResourceTest: the employee action is hidden without a departure date or the create permission', function (): void {
    $stayer = GratuityFixtures::employee(PasiFixtures::company('OM'), 'IN', '2020-01-01', null);
    $leaver = GratuityFixtures::employee(PasiFixtures::company('OM'), 'IN', '2020-01-01', '2023-01-16');
    $employeePermissions = array_values(srPermissions(EmployeeResource::class));

    srUser([...array_values(srPermissions(EndOfServiceSettlementResource::class)), ...$employeePermissions]);
    Livewire::test(ViewEmployee::class, ['record' => $stayer->id])->assertActionHidden('createEndOfServiceSettlement');

    srUser($employeePermissions);
    Livewire::test(ViewEmployee::class, ['record' => $leaver->id])->assertActionHidden('createEndOfServiceSettlement');
});

it('GratuitySettlementResourceTest: the employee action explains why a settlement cannot be created', function (): void {
    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'IN', 900, withPasi: false);
    $employee->update(['departure_date' => '2023-01-16']);
    srUser([
        ...array_values(srPermissions(EndOfServiceSettlementResource::class)),
        ...array_values(srPermissions(EmployeeResource::class)),
    ]);

    Livewire::test(ViewEmployee::class, ['record' => $employee->id])
        ->callAction('createEndOfServiceSettlement')
        ->assertNotified(__('payroll::exceptions.settlement_no_joining_date'));

    expect(EndOfServiceSettlement::query()->count())->toBe(0);
});

it('GratuitySettlementResourceTest: a draft settlement can be edited and the net follows', function (): void {
    $settlement = srSettlement();
    $before = (float) $settlement->net_amount;
    srUser(array_values(srPermissions(EndOfServiceSettlementResource::class)));

    Livewire::test(EditEndOfServiceSettlement::class, ['record' => $settlement->id])
        ->fillForm(['unused_leave_days' => 10, 'other_adjustment' => -25, 'adjustment_note' => 'Advance not on the loan list'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settlement->refresh();

    expect((float) $settlement->leave_payout_amount)->toBe(300.0)
        ->and((float) $settlement->net_amount)->toBe(round($before + 300 - 25, 3))
        ->and($settlement->adjustment_note)->toBe('Advance not on the loan list');
});

it('GratuitySettlementResourceTest: an approved settlement can no longer be edited', function (): void {
    $settlement = srSettlement();
    $settlement->updateQuietly(['status' => SettlementStatus::Approved]);
    srUser(array_values(srPermissions(EndOfServiceSettlementResource::class)));

    Livewire::test(EditEndOfServiceSettlement::class, ['record' => $settlement->id])->assertForbidden();
});

it('GratuitySettlementResourceTest: marking paid works without a flow, then locks the settlement', function (): void {
    $settlement = srSettlement();
    srUser(array_values(srPermissions(EndOfServiceSettlementResource::class)));

    Livewire::test(ViewEndOfServiceSettlement::class, ['record' => $settlement->id])
        ->assertActionVisible('markPaid')
        ->callAction('markPaid')
        ->assertNotified();

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Paid);

    Livewire::test(ViewEndOfServiceSettlement::class, ['record' => $settlement->id])
        ->assertActionHidden('markPaid')
        ->assertActionHidden('cancel');
});

it('GratuitySettlementResourceTest: with an active flow the pay button waits for approval', function (): void {
    $approver = User::withoutEvents(fn (): User => User::factory()->create());
    $flow = srFlow($approver);
    $settlement = srSettlement();
    srUser(array_values(srPermissions(EndOfServiceSettlementResource::class)));

    Livewire::test(ViewEndOfServiceSettlement::class, ['record' => $settlement->id])->assertActionHidden('markPaid');

    $approval = $settlement->submitForApproval($flow, $approver->id);
    app(ApprovalEngine::class)->approve($approval->fresh()->currentStepInstance(), $approver->id);

    Livewire::test(ViewEndOfServiceSettlement::class, ['record' => $settlement->id])
        ->assertActionVisible('markPaid')
        ->callAction('markPaid');

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Paid);
});

it('GratuitySettlementResourceTest: a draft settlement can be cancelled', function (): void {
    $settlement = srSettlement();
    srUser(array_values(srPermissions(EndOfServiceSettlementResource::class)));

    Livewire::test(ViewEndOfServiceSettlement::class, ['record' => $settlement->id])
        ->callAction('cancel');

    expect($settlement->fresh()->status)->toBe(SettlementStatus::Cancelled);
});

it('GratuitySettlementResourceTest: the policy uses the permissions Shield generates', function (): void {
    $settlement = srSettlement();
    $permissions = srPermissions(EndOfServiceSettlementResource::class);
    $user = srUser(array_values($permissions));
    $policy = new EndOfServiceSettlementPolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $settlement))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $settlement))->toBeTrue()
        ->and($policy->cancel($user, $settlement))->toBeTrue()
        ->and($policy->markPaid($user, $settlement))->toBeTrue()
        ->and(method_exists($policy, 'delete'))->toBeFalse();

    $viewer = srUser([$permissions['view_any'], $permissions['view']]);

    expect($policy->update($viewer, $settlement))->toBeFalse()
        ->and($policy->markPaid($viewer, $settlement))->toBeFalse();
});

it('GratuitySettlementResourceTest: the permission sync reaches payroll managers and not viewers, twice', function (): void {
    $keys = collect([
        ...array_values(srPermissions(EndOfServiceSettlementResource::class)),
        ...array_values(srPermissions(GratuityTierResource::class)),
        ...array_keys(FilamentShield::getPages()[ManageGratuitySettings::class]['permissions']),
    ]);

    foreach ($keys as $key) {
        Permission::findOrCreate($key, 'web');
    }

    $manager = Role::findOrCreate('gratuity-sync-manager', 'web');
    $manager->syncPermissions([Permission::findOrCreate('update_payroll_salary::component', 'web')]);
    $viewer = Role::findOrCreate('gratuity-sync-viewer', 'web');
    $viewer->syncPermissions([Permission::findOrCreate('view_any_payroll_salary::component', 'web')]);

    app(ExtensionPermissionsSync::class)->syncPayroll();
    app(ExtensionPermissionsSync::class)->syncPayroll();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ($keys as $key) {
        expect($manager->fresh()->hasPermissionTo($key))->toBeTrue()
            ->and($viewer->fresh()->hasPermissionTo($key))->toBeFalse();
    }
});
