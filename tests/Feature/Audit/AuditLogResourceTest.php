<?php

use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\Audit\AuditEntry;
use App\Support\Audit\AuditRecorder;
use App\Support\ExtensionPermissionsSync;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Chatter\Models\Message;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

const AUDIT_VIEW_ANY = 'view_any_filament_audit::log';

const AUDIT_VIEW = 'view_filament_audit::log';

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->company = Company::query()->first();
    $this->viewer = User::factory()->create(['is_active' => true, 'default_company_id' => $this->company->id]);
    $this->viewer->allowedCompanies()->syncWithoutDetaching([$this->company->id]);

    foreach ([AUDIT_VIEW_ANY, AUDIT_VIEW] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $this->viewer->givePermissionTo($permission);
    }

    $this->actor = User::factory()->create(['is_active' => true, 'default_company_id' => $this->company->id]);
    $this->actingAs($this->viewer);
});

/**
 * @param  array<string, mixed>  $overrides  Column values written straight to the row after it is recorded.
 * @param  array<string, array<string, mixed>>|null  $changes
 */
function auditEntryFor(?object $subject = null, string $event = 'updated', ?array $changes = null, array $overrides = [], ?User $causer = null): AuditEntry
{
    $subject ??= SalaryComponent::factory()->create(['name' => 'Subject '.uniqid()]);
    $causer ??= test()->actor;

    $message = AuditRecorder::record($subject, $event, $changes ?? [
        'name' => ['type' => 'modified', 'old_value' => 'Old '.uniqid(), 'new_value' => 'New '.uniqid()],
    ], $causer);

    expect($message)->not->toBeNull();

    if ($overrides !== []) {
        Message::query()->whereKey($message->id)->toBase()->update($overrides);
    }

    return AuditEntry::query()->findOrFail($message->id);
}

it('AuditLogResourceTest: lists entries newest first for a permitted user', function (): void {
    $oldest = auditEntryFor(overrides: ['created_at' => Carbon::now()->subDays(3)]);
    $newest = auditEntryFor(overrides: ['created_at' => Carbon::now()->subDay()]);
    $middle = auditEntryFor(overrides: ['created_at' => Carbon::now()->subDays(2)]);

    foreach ([$oldest, $newest, $middle] as $entry) {
        Message::query()->whereKey($entry->id)->toBase()->update(['company_id' => $this->company->id]);
    }

    Livewire::test(ListAuditLogs::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$newest, $middle, $oldest], inOrder: true);
});

it('AuditLogResourceTest: filters by user record type event and date range', function (): void {
    $otherActor = User::factory()->create(['is_active' => true]);
    $component = SalaryComponent::factory()->create(['name' => 'Filter '.uniqid()]);

    $byActor = auditEntryFor($component, 'updated', null, ['company_id' => $this->company->id]);
    $byOther = auditEntryFor($component, 'updated', null, ['company_id' => $this->company->id], $otherActor);
    $aUser = auditEntryFor($this->actor, 'created', [
        'name' => ['type' => 'added', 'old_value' => null, 'new_value' => 'X'],
    ], ['company_id' => $this->company->id]);
    $old = auditEntryFor($component, 'soft_deleted', [], [
        'company_id' => $this->company->id,
        'created_at' => Carbon::now()->subDays(40),
    ]);

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$byActor, $byOther, $aUser, $old])
        ->filterTable('causer_id', $otherActor->id)
        ->assertCanSeeTableRecords([$byOther])
        ->assertCanNotSeeTableRecords([$byActor, $aUser, $old])
        ->removeTableFilter('causer_id')
        ->filterTable('messageable_type', $this->actor->getMorphClass())
        ->assertCanSeeTableRecords([$aUser])
        ->assertCanNotSeeTableRecords([$byActor, $byOther, $old])
        ->removeTableFilter('messageable_type')
        ->filterTable('event', 'soft_deleted')
        ->assertCanSeeTableRecords([$old])
        ->assertCanNotSeeTableRecords([$byActor, $byOther, $aUser])
        ->removeTableFilter('event')
        ->filterTable('created_at', ['from' => Carbon::now()->subDays(60)->toDateString(), 'until' => Carbon::now()->subDays(30)->toDateString()])
        ->assertCanSeeTableRecords([$old])
        ->assertCanNotSeeTableRecords([$byActor, $byOther, $aUser]);
});

it('AuditLogResourceTest: searches the summary text', function (): void {
    $match = auditEntryFor(changes: [
        'name' => ['type' => 'modified', 'old_value' => 'Alpha', 'new_value' => 'Zebra stripes'],
    ], overrides: ['company_id' => $this->company->id]);
    $miss = auditEntryFor(changes: [
        'name' => ['type' => 'modified', 'old_value' => 'Alpha', 'new_value' => 'Beta'],
    ], overrides: ['company_id' => $this->company->id]);
    $arabic = auditEntryFor(changes: [
        'name' => ['type' => 'modified', 'old_value' => 'بدل', 'new_value' => 'بدل السكن'],
    ], overrides: ['company_id' => $this->company->id]);

    Livewire::test(ListAuditLogs::class)
        ->searchTable('Zebra')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$miss, $arabic])
        ->searchTable('السكن')
        ->assertCanSeeTableRecords([$arabic])
        ->assertCanNotSeeTableRecords([$match, $miss]);
});

it('AuditLogResourceTest: forbids a user without the permission', function (): void {
    $stranger = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($stranger)->get('/admin/audit-logs')->assertForbidden();

    $this->actingAs($this->viewer)->get('/admin/audit-logs')->assertSuccessful();

    $this->actingAs($stranger);
    Livewire::test(ListAuditLogs::class)->assertForbidden();
});

it('AuditLogResourceTest: shows no create edit or delete actions', function (): void {
    $entry = auditEntryFor(overrides: ['company_id' => $this->company->id]);

    expect(array_keys(AuditLogResource::getPages()))->toBe(['index'])
        ->and(AuditLogResource::canCreate())->toBeFalse()
        ->and(AuditLogResource::canEdit($entry))->toBeFalse()
        ->and(AuditLogResource::canDelete($entry))->toBeFalse()
        ->and(AuditLogResource::canView($entry))->toBeTrue();

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$entry])
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('edit', record: $entry)
        ->assertTableActionDoesNotExist('delete', record: $entry)
        ->assertTableBulkActionDoesNotExist('delete');
});

it('AuditLogResourceTest: only shows entries of the user allowed companies', function (): void {
    $otherCompany = Company::factory()->create();

    $mine = auditEntryFor(overrides: ['company_id' => $this->company->id]);
    $foreign = auditEntryFor(overrides: ['company_id' => $otherCompany->id]);

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);

    $this->viewer->allowedCompanies()->attach($otherCompany->id);

    Livewire::test(ListAuditLogs::class)->assertCanSeeTableRecords([$mine, $foreign]);
});

it('AuditLogResourceTest: renders a deleted record entry without a link', function (): void {
    $component = SalaryComponent::factory()->create(['name' => 'Gone '.uniqid()]);
    $gone = auditEntryFor($component, 'hard_deleted', [
        'name' => ['type' => 'removed', 'old_value' => 'Gone', 'new_value' => null],
    ], ['company_id' => $this->company->id]);

    $component->forceDelete();

    $existing = auditEntryFor($this->actor, 'updated', null, ['company_id' => $this->company->id]);

    Gate::before(fn () => true);

    expect($gone->recordUrl())->toBeNull()
        ->and($existing->recordUrl())->toBeString()->toContain((string) $this->actor->id);

    Livewire::test(ListAuditLogs::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$gone, $existing]);
});

it('AuditLogResourceTest: gives no link to a record the viewer may not open', function (): void {
    $entry = auditEntryFor($this->actor, 'updated', null, ['company_id' => $this->company->id]);

    expect($entry->recordUrl())->toBeNull();
});

it('AuditLogResourceTest: builds the summary from the event and properties, not the stored body', function (): void {
    $entry = auditEntryFor(changes: [
        'name'                             => ['type' => 'modified', 'old_value' => 'Old', 'new_value' => 'New'],
        'some_new_flag'                    => ['type' => 'added', 'old_value' => null, 'new_value' => '2'],
        'password'                         => ['type' => 'modified', 'old_value' => '***', 'new_value' => '***'],
        AuditRecorder::PERMISSIONS_ADDED   => ['type' => 'added', 'old_value' => null, 'new_value' => 'view_x, view_y'],
        AuditRecorder::PERMISSIONS_REMOVED => ['type' => 'removed', 'old_value' => 'view_z', 'new_value' => null],
    ]);
    $entry->forceFill(['body' => 'نص مخزن بلغة أخرى']);

    app()->setLocale('en');
    $english = $entry->summaryLines();

    expect($english)->toContain('Name: Old → New')
        ->and($english)->toContain('Some New Flag: 2')
        ->and($english)->toContain('Permissions added: view_x, view_y')
        ->and($english)->toContain('Permissions removed: view_z')
        ->and(implode('|', $english))->not->toContain('نص مخزن')
        ->and($entry->eventLabel())->toBe('Updated Salary component')
        ->and($entry->subjectLabel())->toBe('Salary component');

    app()->setLocale('ar');

    expect($entry->summaryLines())->toContain('الاسم: Old → New')
        ->and($entry->summaryLines())->toContain('الصلاحيات المضافة: view_x, view_y')
        ->and($entry->eventLabel())->toStartWith('تعديل');

    app()->setLocale('en');
});

it('AuditLogResourceTest: labels every event the recorder can write in both languages', function (): void {
    $events = ['created', 'updated', 'soft_deleted', 'hard_deleted', 'deleted', 'restored', 'login', 'login_failed', 'permissions_changed', 'lock_override', 'lock_date_changed'];

    foreach (['en', 'ar'] as $locale) {
        foreach ($events as $event) {
            expect(trans("audit.events.{$event}", [], $locale))->not->toBe("audit.events.{$event}")
                ->and(trans("audit.event_names.{$event}", [], $locale))->not->toBe("audit.event_names.{$event}");
        }
    }
});

it('AuditLogResourceTest: generates the permission keys the policy and the sync use', function (): void {
    $keys = FilamentShield::getResourcePermissions(AuditLogResource::class);

    expect($keys)->toContain(AUDIT_VIEW_ANY, AUDIT_VIEW);
});

it('AuditLogResourceTest: grants the audit permissions to the right roles only', function (): void {
    $names = ['Admin', 'super_admin', 'general_manager', 'hr_manager'];

    foreach ($names as $name) {
        Role::findOrCreate($name, 'web')->permissions()->detach();
    }

    app(ExtensionPermissionsSync::class)->syncAudit();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['Admin', 'super_admin', 'general_manager'] as $name) {
        expect(Role::findByName($name, 'web')->hasPermissionTo(AUDIT_VIEW_ANY))->toBeTrue()
            ->and(Role::findByName($name, 'web')->hasPermissionTo(AUDIT_VIEW))->toBeTrue();
    }

    expect(Role::findByName('hr_manager', 'web')->permissions->pluck('name')->all())->not->toContain(AUDIT_VIEW_ANY);
});

it('AuditLogResourceTest: shows who, the record type, the event and the readable change in the table', function (): void {
    auditEntryFor(changes: [
        'name' => ['type' => 'modified', 'old_value' => 'Before value', 'new_value' => 'After value'],
    ], overrides: ['company_id' => $this->company->id]);

    Livewire::test(ListAuditLogs::class)
        ->assertSee($this->actor->name)
        ->assertSee('Salary component')
        ->assertSee('Updated')
        ->assertSee('Name: Before value → After value');
});
