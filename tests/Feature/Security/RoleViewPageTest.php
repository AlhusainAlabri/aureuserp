<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Webkul\Security\Filament\Resources\RoleResource\Pages\ViewRole;
use Webkul\Security\Models\Role;
use Webkul\Security\Models\User;

it('renders the role view page with its module access summary', function (): void {
    Gate::before(fn (): bool => true);
    $this->actingAs(User::factory()->create());

    $role = Role::query()->create(['name' => 'qa_view_role', 'guard_name' => 'web']);

    Livewire::test(ViewRole::class, ['record' => $role->getKey()])->assertSuccessful();
});
