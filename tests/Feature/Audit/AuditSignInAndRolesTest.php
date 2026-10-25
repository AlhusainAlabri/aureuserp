<?php

use App\Support\Audit\AuditRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Chatter\Models\Message;
use Webkul\Security\Filament\Resources\RoleResource\Pages\CreateRole;
use Webkul\Security\Filament\Resources\RoleResource\Pages\EditRole;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap();
});

function signInEntries(string $event)
{
    return Message::query()->where('log_name', AuditRecorder::LOG_NAME)->where('event', $event);
}

it('AuditSignInAndRolesTest: records a successful login on the user and a failed login for an existing user', function (): void {
    $user = User::factory()->create();

    event(new Login('web', $user, false));

    $login = signInEntries('login')->where('messageable_id', $user->id)->first();

    expect($login)->not->toBeNull()
        ->and($login->messageable_type)->toBe($user->getMorphClass())
        ->and($login->causer_id)->toBe($user->id);

    event(new Failed('web', $user, ['email' => $user->email, 'password' => 'secret-attempt']));

    $failed = signInEntries('login_failed')->where('messageable_id', $user->id)->first();

    expect($failed)->not->toBeNull()
        ->and($failed->causer_id)->toBe($user->id)
        ->and(json_encode($failed->properties))->not->toContain('secret-attempt');
});

it('AuditSignInAndRolesTest: records nothing for a failed login with no known user', function (): void {
    $before = signInEntries('login_failed')->count();

    event(new Failed('web', null, ['email' => 'nobody@example.test', 'password' => 'x']));

    expect(signInEntries('login_failed')->count())->toBe($before);
});

it('AuditSignInAndRolesTest: records the permissions added and removed when a role is saved from the role pages', function (): void {
    Gate::before(fn (): bool => true);
    $this->actingAs(User::factory()->create());

    $role = Role::query()->create(['name' => 'audit_role_'.uniqid(), 'guard_name' => 'web']);
    $role->syncPermissionsByNames(['audit_perm_keep', 'audit_perm_drop']);

    Livewire::test(EditRole::class, ['record' => $role->getKey()])
        ->fillForm([
            'advanced_editor_open' => true,
            'advanced_permissions' => ['audit_perm_keep', 'audit_perm_new'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $entries = signInEntries('permissions_changed')->where('messageable_id', $role->id)->get();

    expect($entries)->toHaveCount(1);

    $entry = $entries->first();

    expect($entry->messageable_type)->toBe($role->getMorphClass())
        ->and($entry->properties['Permissions added']['new_value'])->toBe('audit_perm_new')
        ->and($entry->properties['Permissions removed']['old_value'])->toBe('audit_perm_drop');

    Livewire::test(EditRole::class, ['record' => $role->getKey()])
        ->fillForm([
            'advanced_editor_open' => true,
            'advanced_permissions' => ['audit_perm_keep', 'audit_perm_new'],
        ])
        ->call('save');

    expect(signInEntries('permissions_changed')->where('messageable_id', $role->id)->count())->toBe(1);
});

it('AuditSignInAndRolesTest: records the permissions a new role is created with', function (): void {
    Gate::before(fn (): bool => true);
    $this->actingAs(User::factory()->create());

    $name = 'audit_new_role_'.uniqid();

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name'                 => $name,
            'advanced_editor_open' => true,
            'advanced_permissions' => ['audit_perm_a', 'audit_perm_b'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::query()->where('name', $name)->firstOrFail();

    $entry = signInEntries('permissions_changed')->where('messageable_id', $role->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->properties['Permissions added']['new_value'])->toBe('audit_perm_a, audit_perm_b')
        ->and($entry->properties)->not->toHaveKey('Permissions removed');
});
