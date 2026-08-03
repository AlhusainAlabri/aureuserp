<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Employee\Models\Employee;

class ExtensionPermissionsSync
{
    public function syncAll(): void
    {
        if (! PermissionTables::areReady()) {
            return;
        }

        $this->syncHr();
        $this->syncAssets();
        $this->syncInventory();
        $this->syncPurchase();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncHr(): void
    {
        if (! class_exists(Role::class) || ! class_exists(Employee::class) || ! PermissionTables::areReady()) {
            return;
        }

        $permissions = [
            'close_employee_file',
            'reopen_employee_file',
            'review_self_assessment',
            'view_any_self_assessment',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (['Admin', 'hr_manager', 'super_admin'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        foreach (['manager', 'department_manager'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo(['review_self_assessment']);
            }
        }
    }

    public function syncAssets(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady() || ! SchemaMemo::hasTable('assets')) {
            return;
        }

        $permissions = [
            'page_assets_dashboard',
            'page_my_borrowing_requests',
            'page_pending_borrowing_requests',
            'page_my_borrowed_assets',
            'request_borrow_assets_asset',
            'approve_borrowing_assets_asset',
            'reject_borrowing_assets_asset',
            'view_assets_asset_borrowing',
            'view_any_assets_asset_borrowing',
            'widget_assets_summary_widget',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $managerRoles = ['Admin', 'admin_manager', 'super_admin', 'general_manager', 'hr_manager', 'manager'];
        $employeePermissions = [
            'page_my_borrowing_requests',
            'page_my_borrowed_assets',
            'request_borrow_assets_asset',
        ];

        foreach ($managerRoles as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($permissions as $permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        foreach (['employee'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($employeePermissions as $permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function syncInventory(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady() || ! SchemaMemo::hasTable('inventories_order_points')) {
            return;
        }

        $permissions = [
            'page_inventory_dashboard',
            'page_inventory_movement_report',
            'page_inventory_record_consumption',
            'page_inventory_product_purchase_history',
            'reorder_inventory_replenishment',
            'widget_filament_low_stock_widget',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['Admin', 'admin_manager', 'super_admin', 'general_manager', 'finance_manager', 'manager'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            foreach ($permissions as $permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function syncPurchase(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady()) {
            return;
        }

        $permissions = [
            'page_purchases_department_report',
            'page_MyRequests',
            'page_InternalRequests',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web'],
            );
        }

        foreach (['Admin', 'admin_manager', 'super_admin', 'general_manager', 'finance_manager', 'manager'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }
}
