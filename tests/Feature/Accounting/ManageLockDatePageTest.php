<?php

use App\Filament\Pages\Settings\ManageLockDate;
use App\Support\Accounting\PeriodLock;
use App\Support\ExtensionPermissionsSync;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Chatter\Models\Message;
use Webkul\Security\Filament\Resources\RoleResource;
use Webkul\Security\Filament\Resources\RoleResource\Pages\EditRole;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

const LOCK_DATE_PAGE_PERMISSION = 'page_filament_manage_lock_date';

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap();

    PeriodLock::forget();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();

    $this->accountant = User::factory()->create(['is_active' => true, 'default_company_id' => $this->company->id]);
    $this->accountant->allowedCompanies()->syncWithoutDetaching([$this->company->id, $this->otherCompany->id]);

    Permission::findOrCreate(LOCK_DATE_PAGE_PERMISSION, 'web');
    $this->accountant->givePermissionTo(LOCK_DATE_PAGE_PERMISSION);

    $this->lockDate = CarbonImmutable::today()->subMonthsNoOverflow(2)->endOfMonth();

    $this->actingAs($this->accountant);
});

afterEach(function (): void {
    PeriodLock::forget();
});

function storedLockDate(Company $company): ?string
{
    $value = DB::table('companies')->where('id', $company->id)->value(PeriodLock::COLUMN);

    return $value ? CarbonImmutable::parse($value)->toDateString() : null;
}

it('ManageLockDatePageTest: saves the lock date for the chosen company and logs it', function (): void {
    Livewire::test(ManageLockDate::class)
        ->assertFormSet(['company_id' => $this->company->id, 'lock_date' => null])
        ->fillForm(['lock_date' => $this->lockDate->toDateString()])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(storedLockDate($this->company))->toBe($this->lockDate->toDateString())
        ->and(storedLockDate($this->otherCompany))->toBeNull();

    $entry = Message::query()
        ->where('log_name', 'audit')
        ->where('event', 'lock_date_changed')
        ->where('messageable_id', $this->company->id)
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->causer_id)->toBe($this->accountant->id)
        ->and($entry->properties[PeriodLock::COLUMN]['new_value'])->toBe($this->lockDate->toDateString());
});

it('ManageLockDatePageTest: rejects a future date', function (): void {
    Livewire::test(ManageLockDate::class)
        ->fillForm(['lock_date' => CarbonImmutable::tomorrow()->toDateString()])
        ->call('save')
        ->assertHasFormErrors(['lock_date']);

    expect(storedLockDate($this->company))->toBeNull();
});

it('ManageLockDatePageTest: can clear the date', function (): void {
    PeriodLock::setLockDate($this->company->id, $this->lockDate, $this->accountant);

    Livewire::test(ManageLockDate::class)
        ->assertFormSet(['lock_date' => $this->lockDate->toDateString()])
        ->fillForm(['lock_date' => null])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(storedLockDate($this->company))->toBeNull();
});

it('ManageLockDatePageTest: shows the saved date when the company changes', function (): void {
    $otherDate = $this->lockDate->subMonthNoOverflow()->endOfMonth();

    PeriodLock::setLockDate($this->company->id, $this->lockDate, $this->accountant);
    PeriodLock::setLockDate($this->otherCompany->id, $otherDate, $this->accountant);

    Livewire::test(ManageLockDate::class)
        ->assertFormSet(['company_id' => $this->company->id, 'lock_date' => $this->lockDate->toDateString()])
        ->fillForm(['company_id' => $this->otherCompany->id])
        ->assertFormSet(['lock_date' => $otherDate->toDateString()]);
});

it('ManageLockDatePageTest: only offers the companies the user may use', function (): void {
    $foreign = Company::factory()->create();

    Livewire::test(ManageLockDate::class)
        ->fillForm(['company_id' => $foreign->id, 'lock_date' => $this->lockDate->toDateString()])
        ->call('save')
        ->assertHasFormErrors(['company_id']);

    expect(storedLockDate($foreign))->toBeNull();
});

it('ManageLockDatePageTest: is forbidden without the page permission and open to Admin', function (): void {
    $nobody = User::factory()->create(['is_active' => true, 'default_company_id' => $this->company->id]);
    $nobody->allowedCompanies()->syncWithoutDetaching([$this->company->id]);

    $this->actingAs($nobody);
    Livewire::test(ManageLockDate::class)->assertForbidden();

    app(ExtensionPermissionsSync::class)->syncAccounting();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = User::factory()->create(['is_active' => true, 'default_company_id' => $this->company->id]);
    $admin->allowedCompanies()->syncWithoutDetaching([$this->company->id]);
    $admin->assignRole(Role::findOrCreate('Admin', 'web'));

    $this->actingAs($admin);
    Livewire::test(ManageLockDate::class)->assertSuccessful();
});

it('ManageLockDatePageTest: pins the page permission key to what Shield generates', function (): void {
    $keys = array_keys(FilamentShield::getPages()[ManageLockDate::class]['permissions'] ?? []);

    expect($keys)->toBe([LOCK_DATE_PAGE_PERMISSION]);
});

it('ManageLockDatePageTest: the sync grants the lock and audit permissions to the right roles', function (): void {
    foreach (['Admin', 'super_admin', 'finance_manager', 'general_manager', 'hr_manager'] as $name) {
        Role::findOrCreate($name, 'web')->permissions()->detach();
    }

    app(ExtensionPermissionsSync::class)->syncAll();

    foreach (['Admin', 'super_admin', 'finance_manager'] as $name) {
        $role = Role::findByName($name, 'web');

        expect($role->hasPermissionTo(LOCK_DATE_PAGE_PERMISSION))->toBeTrue("{$name} page")
            ->and($role->hasPermissionTo(PeriodLock::OVERRIDE_PERMISSION))->toBeTrue("{$name} override");
    }

    $admin = Role::findByName('Admin', 'web');
    expect($admin->hasPermissionTo('view_any_filament_audit::log'))->toBeTrue();

    foreach (['general_manager', 'hr_manager'] as $name) {
        $role = Role::findByName($name, 'web');

        expect($role->hasPermissionTo(LOCK_DATE_PAGE_PERMISSION))->toBeFalse("{$name} page")
            ->and($role->hasPermissionTo(PeriodLock::OVERRIDE_PERMISSION))->toBeFalse("{$name} override");
    }
});

it('ManageLockDatePageTest: lists the override permission among the permissions the Roles screen offers', function (): void {
    expect(array_keys(FilamentShield::getCustomPermissions()))->toContain(PeriodLock::OVERRIDE_PERMISSION)
        ->and(RoleResource::getAllFormPermissions()->all())->toContain(PeriodLock::OVERRIDE_PERMISSION);
});

it('ManageLockDatePageTest: keeps the override on a finance role saved through the Roles screen with the box ticked', function (): void {
    Gate::before(fn (): bool => true);

    $role = Role::findOrCreate('finance_manager', 'web');
    $role->permissions()->detach();

    app(ExtensionPermissionsSync::class)->syncAccounting();

    $granted = $role->permissions()->pluck('name')->all();

    expect($granted)->toContain(PeriodLock::OVERRIDE_PERMISSION);

    Livewire::test(EditRole::class, ['record' => $role->getKey()])
        ->assertFormSet(fn (array $state): bool => in_array(PeriodLock::OVERRIDE_PERMISSION, $state['advanced_permissions'] ?? [], true))
        ->fillForm([
            'advanced_editor_open' => true,
            'advanced_permissions' => $granted,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->fresh()->hasPermissionTo(PeriodLock::OVERRIDE_PERMISSION))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo(LOCK_DATE_PAGE_PERMISSION))->toBeTrue();
});
