<?php

namespace Database\Seeders;

use App\Enums\Purchases\RequestType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Rawilk\Settings\Facades\Settings;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Partner\Enums\AccountType;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Category;
use Webkul\Product\Models\Product;
use Webkul\Purchase\Models\Order;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

class PurchaseExtensionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('purchases_orders')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedRoles();
        $this->seedPermissions();
        $this->seedDefaultCurrency();
        $this->seedTestProduct();
        $this->seedMiscSupplier();
        $this->assignAdminRoles();
    }

    protected function seedRoles(): void
    {
        foreach (['finance_manager', 'general_manager', 'manager'] as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );
        }
    }

    protected function seedPermissions(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'page_purchases_department_report'],
            ['guard_name' => 'web'],
        );

        foreach (['Admin', 'admin_manager', 'super_admin', 'general_manager', 'finance_manager', 'manager'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    protected function seedDefaultCurrency(): void
    {
        if (! Schema::hasTable('support_currencies')) {
            return;
        }

        $omrId = Currency::query()
            ->where('name', 'OMR')
            ->orWhere('full_name', 'like', '%Omani%')
            ->value('id');

        if (! $omrId) {
            return;
        }

        if (Schema::hasTable('support_companies') && Schema::hasColumn('support_companies', 'currency_id')) {
            Company::query()->update(['currency_id' => $omrId]);
        }

        if (Schema::hasTable('purchases_orders') && Schema::hasColumn('purchases_orders', 'currency_id')) {
            Order::query()
                ->whereIn('request_type', RequestType::internalRequestTypes())
                ->update(['currency_id' => $omrId]);
        }

        if (Schema::hasTable('settings') || class_exists(Settings::class)) {
            try {
                settings(['currency.default_currency_id' => $omrId]);
            } catch (\Throwable) {
                // Settings table may not be migrated yet.
            }
        }
    }

    protected function seedTestProduct(): void
    {
        if (! Schema::hasTable('products_products')) {
            return;
        }

        $companyId = Company::query()->value('id');
        $uomId = UOM::query()->value('id');
        $categoryId = Category::query()->value('id');

        if (! $companyId || ! $uomId) {
            return;
        }

        Product::query()->updateOrCreate(
            ['name' => 'General Purchase Item'],
            [
                'type'            => ProductType::GOODS,
                'reference'       => 'GEN-PUR-001',
                'barcode'         => 'GENPUR001',
                'price'           => 0,
                'cost'            => 0,
                'enable_sales'    => false,
                'enable_purchase' => true,
                'company_id'      => $companyId,
                'uom_id'          => $uomId,
                'uom_po_id'       => $uomId,
                'category_id'     => $categoryId,
                'creator_id'      => 1,
            ],
        );
    }

    protected function seedMiscSupplier(): void
    {
        if (! Schema::hasTable('partners_partners')) {
            return;
        }

        $companyId = Company::query()->value('id');

        if (! $companyId) {
            return;
        }

        Partner::query()->firstOrCreate(
            ['name' => 'Misc Supplier'],
            [
                'account_type' => AccountType::COMPANY,
                'company_id'   => $companyId,
                'creator_id'   => User::query()->value('id') ?? 1,
            ],
        );
    }

    protected function assignAdminRoles(): void
    {
        $admin = User::query()->where('email', 'nodhumtech@gmail.com')->first();

        if (! $admin) {
            return;
        }

        foreach (['Admin', 'manager', 'finance_manager', 'general_manager'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $admin->hasRole($roleName)) {
                $admin->assignRole($role);
            }
        }
    }
}
