<?php

use App\Support\Navigation\QuickCreateMenu;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    if (! Schema::hasTable('meetings')) {
        Artisan::call('meetings:install', ['--no-interaction' => true]);
    }
});

function quickCreateUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

it('does not expose resource shortcuts without create permission', function (): void {
    quickCreateUser();

    $urls = collect(QuickCreateMenu::groups())
        ->flatMap(fn (array $group): array => collect($group['items'])->pluck('url')->all())
        ->all();

    expect($urls)->not->toContain(MeetingResource::getUrl('create'));
});

it('shows meeting shortcuts only when the user can create meetings', function (): void {
    quickCreateUser(['create_meetings_meeting']);

    $groups = QuickCreateMenu::groups();

    expect(QuickCreateMenu::isVisible())->toBeTrue();

    $meetingGroup = collect($groups)->first(
        fn (array $group): bool => collect($group['items'])->contains(
            fn (array $item): bool => str_contains($item['url'], '/meetings/meetings/create'),
        ),
    );

    expect($meetingGroup)->not->toBeNull()
        ->and($meetingGroup['items'])->toHaveCount(1)
        ->and($meetingGroup['items'][0]['url'])->toContain('/meetings/meetings/create');
});

it('filters out groups that have no accessible items', function (): void {
    quickCreateUser(['create_meetings_meeting']);

    $groups = QuickCreateMenu::groups();

    foreach ($groups as $group) {
        expect($group['items'])->not->toBeEmpty()
            ->and($group['label'])->not->toBeEmpty()
            ->and($group['icon'])->not->toBeEmpty();
    }
});

it('renders the quick create component for users with accessible shortcuts', function (): void {
    quickCreateUser(['create_meetings_meeting']);

    $html = view('filament.components.quick-create')->render();

    expect($html)
        ->toContain(__('admin.quick_create.button'))
        ->toContain(__('admin.quick_create.heading'))
        ->toContain('/meetings/meetings/create');
});

it('does not render the quick create component for guests', function (): void {
    auth()->logout();

    $html = trim(view('filament.components.quick-create')->render());

    expect($html)->toBe('');
});
