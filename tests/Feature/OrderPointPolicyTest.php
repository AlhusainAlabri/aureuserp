<?php

use App\Policies\OrderPointPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory order points table is not available.');
    }

    Gate::policy(OrderPoint::class, OrderPointPolicy::class);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('authorizes viewAny with only the authenticated user', function (): void {
    Permission::findOrCreate('view_any_inventory_replenishment', 'web');
    $this->user->givePermissionTo('view_any_inventory_replenishment');

    expect($this->user->can('viewAny', OrderPoint::class))->toBeTrue();
});

it('denies viewAny without permission', function (): void {
    expect($this->user->can('viewAny', OrderPoint::class))->toBeFalse();
});

it('authorizes class-level bulk abilities with only the authenticated user', function (string $ability, string $permission): void {
    Permission::findOrCreate($permission, 'web');
    $this->user->givePermissionTo($permission);

    expect($this->user->can($ability, OrderPoint::class))->toBeTrue();
})->with([
    'deleteAny'      => ['deleteAny', 'delete_any_inventory_replenishment'],
    'restoreAny'     => ['restoreAny', 'restore_any_inventory_replenishment'],
    'forceDeleteAny' => ['forceDeleteAny', 'force_delete_any_inventory_replenishment'],
    'reorder'        => ['reorder', 'reorder_inventory_replenishment'],
]);

it('authorizes view on a specific order point', function (): void {
    Permission::findOrCreate('view_inventory_replenishment', 'web');
    $this->user->givePermissionTo('view_inventory_replenishment');

    $orderPoint = OrderPoint::factory()->create();

    expect($this->user->can('view', $orderPoint))->toBeTrue();
});
