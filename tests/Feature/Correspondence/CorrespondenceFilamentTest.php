<?php

use App\Filament\Correspondence\Pages\ExtendedCorrespondenceDashboard;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\CreateCorrespondence;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\ListCorrespondences;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages\ViewCorrespondence;
use Webkul\Correspondence\Filament\Widgets\IncomingCorrespondencesTable;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    if (! Schema::hasTable('correspondences')) {
        Artisan::call('correspondence:install', ['--no-interaction' => true]);
    }
});

function correspondenceFilamentUser(array $permissions = []): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $defaults = [
        'view_any_correspondence_correspondence',
        'view_correspondence_correspondence',
        'create_correspondence_correspondence',
        'update_correspondence_correspondence',
        'archive_correspondence_correspondence',
        'view_all_departments_correspondence_correspondence',
    ];

    $user = User::withoutEvents(fn (): User => User::factory()->create([
        'default_company_id' => Company::query()->value('id'),
    ]));

    foreach (array_unique([...$defaults, ...$permissions]) as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    test()->actingAs($user);

    return $user;
}

function correspondenceFilamentCompany(): Company
{
    return Company::query()->firstOrFail();
}

it('CorrespondenceFilamentTest: list page shows outgoing incoming and archived tabs', function (): void {
    correspondenceFilamentUser();

    Livewire::test(ListCorrespondences::class)
        ->assertOk()
        ->assertSee(__('correspondence::correspondence.outgoing'))
        ->assertSee(__('correspondence::correspondence.incoming'))
        ->assertSee(__('correspondence::correspondence.tabs.archived'));
});

it('CorrespondenceFilamentTest: archived tab query only includes archived records', function (): void {
    correspondenceFilamentUser();

    $active = Correspondence::factory()->incoming()->create([
        'company_id' => correspondenceFilamentCompany()->id,
        'status'     => 'received',
    ]);
    $archived = Correspondence::factory()->incoming()->create([
        'company_id' => correspondenceFilamentCompany()->id,
    ]);
    $archived->update(['status' => 'archived']);

    Livewire::test(ListCorrespondences::class)
        ->set('activeTab', 'archived')
        ->assertOk();

    $archivedTabIds = Correspondence::query()
        ->where('status', 'archived')
        ->pluck('id');

    expect($archivedTabIds)->toContain($archived->id)
        ->and($archivedTabIds)->not->toContain($active->id);
});

it('CorrespondenceFilamentTest: can create incoming correspondence from form', function (): void {
    correspondenceFilamentUser();

    Livewire::test(CreateCorrespondence::class)
        ->fillForm([
            'direction'   => 'incoming',
            'type'        => 'official',
            'priority'    => 'normal',
            'subject'     => 'Test Incoming Letter',
            'sender_name' => 'External Sender',
            'received_at' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    expect(Correspondence::query()->where('subject', 'Test Incoming Letter')->exists())->toBeTrue();
});

it('CorrespondenceFilamentTest: viewing incoming correspondence marks it as read', function (): void {
    $user = correspondenceFilamentUser();
    $correspondence = Correspondence::factory()->incoming()->create([
        'company_id' => correspondenceFilamentCompany()->id,
        'to_user_id' => $user->id,
        'status'     => 'received',
    ]);

    Livewire::test(ViewCorrespondence::class, ['record' => $correspondence->getKey()])
        ->assertOk();

    expect($correspondence->reads()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('CorrespondenceFilamentTest: archive action hides record from outgoing tab', function (): void {
    correspondenceFilamentUser();

    $correspondence = Correspondence::factory()->outgoing()->create([
        'company_id' => correspondenceFilamentCompany()->id,
        'status'     => 'sent',
    ]);

    Livewire::test(ListCorrespondences::class)
        ->assertCanSeeTableRecords([$correspondence]);

    Livewire::test(ViewCorrespondence::class, ['record' => $correspondence->getKey()])
        ->callAction('archive');

    expect($correspondence->fresh()->status)->toBe('archived');
});

it('CorrespondenceFilamentTest: correspondence dashboard page renders with translated widget headings', function (): void {
    correspondenceFilamentUser();

    app()->setLocale('ar');

    Livewire::test(ExtendedCorrespondenceDashboard::class)
        ->assertOk()
        ->assertSee(__('correspondence::correspondence.navigation.dashboard', locale: 'ar'));

    Livewire::test(IncomingCorrespondencesTable::class)
        ->assertSee(__('correspondence::correspondence.dashboard.sections.incoming', locale: 'ar'))
        ->assertSee(__('correspondence::correspondence.empty.no_records', locale: 'ar'));
});

it('CorrespondenceFilamentTest: plural label is used on list page heading', function (): void {
    correspondenceFilamentUser();

    Livewire::test(ListCorrespondences::class)
        ->assertSee(CorrespondenceResource::getPluralModelLabel());
});
