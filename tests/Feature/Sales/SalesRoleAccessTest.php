<?php

use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'sales' => 'sales_orders',
    ]);

    $this->artisan('erp:seed-starter-roles')->assertSuccessful();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // The test database is shared: remove the users this test adds so that other tests,
    // which pick "the first/last user", are not run as a record-scoped salesperson.
    $this->lastUserId = (int) User::query()->max('id');

    $this->salesperson = User::factory()->create(['resource_permission' => 'individual']);
    $this->salesperson->assignRole('sales_manager');
});

afterEach(function (): void {
    User::query()->where('id', '>', $this->lastUserId)->get()->each(function (User $user): void {
        $user->roles()->detach();
        $user->forceDelete();
    });
});

it('limits an individual-scope salesperson to their own quotations', function (): void {
    $mine = Order::factory()->create(['state' => OrderState::DRAFT, 'user_id' => $this->salesperson->id]);
    $theirs = Order::factory()->create(['state' => OrderState::DRAFT, 'user_id' => User::factory()->create()->id]);

    $this->actingAs($this->salesperson);

    $visible = Order::applyPermissionScope(Order::query())->pluck('id');

    expect($visible)->toContain($mine->id)
        ->not->toContain($theirs->id);
});

it('keeps the sales role out of accounting, payroll and destructive sales actions', function (): void {
    $role = Role::findByName('sales_manager');

    foreach (['view_any_account_move', 'view_any_payroll_payslip', 'delete_any_sale_order', 'force_delete_any_sale_order'] as $permission) {
        expect($role->permissions->pluck('name')->contains($permission))->toBeFalse($permission);
    }

    expect($role->permissions->pluck('name')->contains('create_sale_quotation'))->toBeTrue();
});
