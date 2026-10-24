<?php

use Webkul\Security\Models\User;

it('lists account tags for an administrator', function (): void {
    $admin = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $admin->assignRole('Admin');

    $this->actingAs($admin)->get('/admin/account-tags')->assertSuccessful();
});
