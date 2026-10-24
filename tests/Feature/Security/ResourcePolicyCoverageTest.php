<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Webkul\Security\Models\User;

it('guards every admin resource that has pages with a policy', function (): void {
    $panel = Filament::getPanel('admin');

    // Base classes that only exist to be extended: they register no pages or routes.
    $withoutPages = [
        'Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource',
        'Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource',
    ];

    $unguarded = collect($panel->getResources())
        ->reject(fn (string $resource): bool => in_array($resource, $withoutPages, true))
        ->filter(fn (string $resource): bool => class_exists($resource::getModel()))
        ->filter(fn (string $resource): bool => Gate::getPolicyFor($resource::getModel()) === null)
        ->values()
        ->all();

    expect($unguarded)->toBe([], 'Resources without a policy are open to every signed-in user: '.implode(', ', $unguarded));
});

it('hides sensitive admin pages from a user without permissions', function (string $url): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user)->get($url)->assertForbidden();
})->with([
    '/admin/approval-flows',
    '/admin/approval-flows/create',
    '/admin/payroll/employee-components',
    '/admin/settings/email-templates',
    '/admin/settings/email-logs',
]);

it('still opens those pages for an administrator', function (string $url): void {
    $admin = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $admin->assignRole('Admin');

    $this->actingAs($admin)->get($url)->assertSuccessful();
})->with([
    '/admin/approval-flows',
    '/admin/payroll/employee-components',
    '/admin/settings/email-templates',
]);
