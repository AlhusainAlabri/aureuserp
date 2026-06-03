<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Exceptions\MissingSettings;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Settings\WarehouseSettings;
use Webkul\Support\Models\Company;

class InventoryExtensionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('inventories_warehouses')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedPermissions();
        $this->enableWarehouseLocations();
        $this->seedConsumptionLocations();
    }

    protected function enableWarehouseLocations(): void
    {
        if (! class_exists(WarehouseSettings::class)) {
            return;
        }

        try {
            $settings = app(WarehouseSettings::class);
        } catch (MissingSettings) {
            return;
        }

        if ($settings->enable_locations) {
            return;
        }

        $settings->enable_locations = true;
        $settings->save();
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            'page_inventory_dashboard',
            'page_inventory_movement_report',
            'page_inventory_record_consumption',
            'page_inventory_product_purchase_history',
            'reorder_inventory_replenishment',
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

    protected function seedConsumptionLocations(): void
    {
        $consumptionName = __('inventory-extensions::locations.consumption');

        Warehouse::query()->each(function (Warehouse $warehouse) use ($consumptionName): void {
            $parentId = $warehouse->lot_stock_location_id;

            if (! $parentId) {
                return;
            }

            $exists = Location::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('name', $consumptionName)
                ->exists();

            if ($exists) {
                return;
            }

            $parent = Location::query()->find($parentId);

            Location::query()->create([
                'name'         => $consumptionName,
                'full_name'    => ($parent?->full_name ? $parent->full_name.' / ' : '').$consumptionName,
                'type'         => LocationType::INTERNAL,
                'parent_id'    => $parentId,
                'warehouse_id' => $warehouse->id,
                'company_id'   => $warehouse->company_id ?? Company::query()->value('id'),
                'creator_id'   => 1,
            ]);
        });
    }
}
