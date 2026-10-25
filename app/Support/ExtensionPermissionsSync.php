<?php

namespace App\Support;

use App\Filament\Pages\Settings\ManageLockDate;
use App\Support\Accounting\PeriodLock;
use Illuminate\Support\Facades\Schema;
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
        $this->syncNavigation();
        $this->syncBranding();
        $this->syncPayroll();
        $this->syncAttendance();
        $this->syncOperations();
        $this->syncAudit();
        $this->syncAccounting();

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
            'view_any_assets_asset',
            'view_assets_asset',
            'create_assets_asset',
            'update_assets_asset',
            'delete_assets_asset',
            'delete_any_assets_asset',
            'borrow_assets_asset',
            'return_asset_assets_asset',
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

    /**
     * @param  list<string>  $permissions
     * @param  list<string>  $roleNames
     */
    private function grantPermissionsToRoles(array $permissions, array $roleNames): void
    {
        $permissionIds = collect($permissions)
            ->map(fn (string $name): int => Permission::query()->firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ])->getKey())
            ->all();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($roleNames as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    public function syncNavigation(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady()) {
            return;
        }

        $this->grantPermissionsToRoles(
            ['page_filament_manage_navigation'],
            ['Admin', 'admin_manager', 'super_admin', 'general_manager'],
        );
    }

    public function syncBranding(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady()) {
            return;
        }

        $this->grantPermissionsToRoles(
            ['page_filament_manage_branding'],
            ['Admin', 'admin_manager', 'super_admin', 'general_manager'],
        );
    }

    /**
     * PASI rules arrived after payroll roles were created: whoever can already update salary
     * components may also manage PASI rules and read the contribution schedule. The same roles manage the
     * gratuity tiers and settings, end-of-service settlements and the attendance-in-payroll switches.
     */
    public function syncPayroll(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady() || ! Schema::hasTable('payroll_pasi_rules')) {
            return;
        }

        $roleNames = Role::query()
            ->where('guard_name', 'web')
            ->whereHas('permissions', fn ($query) => $query->where('name', 'update_payroll_salary::component'))
            ->pluck('name')
            ->all();

        $this->grantPermissionsToRoles([
            'view_any_payroll_pasi::rule',
            'view_payroll_pasi::rule',
            'create_payroll_pasi::rule',
            'update_payroll_pasi::rule',
            'page_payroll_pasi_contribution_schedule',
            'view_any_payroll_gratuity::tier',
            'view_payroll_gratuity::tier',
            'create_payroll_gratuity::tier',
            'update_payroll_gratuity::tier',
            'page_payroll_manage_gratuity_settings',
            'page_payroll_manage_payroll_attendance_settings',
            'view_any_payroll_end::of::service::settlement',
            'view_payroll_end::of::service::settlement',
            'create_payroll_end::of::service::settlement',
            'update_payroll_end::of::service::settlement',
        ], $roleNames);
    }

    /**
     * Attendance arrived after the HR roles were created. Team leads and HR read the day and overtime
     * lists (the plugin narrows what each of them sees to their own team); only HR and admins edit days,
     * decide overtime, import from the device and change the attendance settings. The monthly summary page is
     * read-only and follows the day list: it narrows what each user sees to their own team. Adds, never removes.
     */
    public function syncAttendance(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady() || ! Schema::hasTable('attendance_days')) {
            return;
        }

        $this->grantPermissionsToRoles([
            'view_any_attendance_attendance::day',
            'view_attendance_attendance::day',
            'view_any_attendance_overtime::request',
            'view_attendance_overtime::request',
            'page_attendance_monthly_attendance_summary',
        ], ['manager', 'department_manager', 'hr_manager', 'Admin', 'super_admin']);

        $this->grantPermissionsToRoles([
            'create_attendance_attendance::day',
            'update_attendance_attendance::day',
            'create_attendance_overtime::request',
            'update_attendance_overtime::request',
            'page_attendance_import_attendance',
            'page_attendance_manage_attendance_settings',
        ], ['hr_manager', 'Admin', 'super_admin']);
    }

    public function syncOperations(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady()) {
            return;
        }

        $this->grantPermissionsToRoles(
            [
                'page_system_health_dashboard',
                'page_manage_operations',
                'run_system_health_check',
                'manage_operations_settings',
                'view_any_system_backup',
                'view_system_backup',
                'create_system_backup',
                'download_system_backup',
                'delete_system_backup',
                'restore_system_backup',
            ],
            ['Admin', 'super_admin', 'general_manager'],
        );
    }

    /**
     * The audit log is read-only, so there is nothing to grant beyond reading it.
     */
    public function syncAudit(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady()) {
            return;
        }

        $this->grantPermissionsToRoles(
            [
                'view_any_filament_audit::log',
                'view_filament_audit::log',
            ],
            ['Admin', 'super_admin', 'general_manager'],
        );
    }

    /**
     * The lock date page and the right to post in a closed period go to the finance roles only. The override
     * is granted on purpose: every use of it is written to the audit log.
     */
    public function syncAccounting(): void
    {
        if (! class_exists(Permission::class) || ! PermissionTables::areReady()) {
            return;
        }

        $this->grantPermissionsToRoles(
            [
                ManageLockDate::PERMISSION,
                PeriodLock::OVERRIDE_PERMISSION,
            ],
            ['Admin', 'super_admin', 'finance_manager'],
        );
    }
}
