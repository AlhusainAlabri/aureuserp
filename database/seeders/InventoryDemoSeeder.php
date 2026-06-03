<?php

namespace Database\Seeders;

use App\Models\Inventory\InventoryConsumptionLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Department;
use Webkul\Inventory\Database\Factories\MoveFactory;
use Webkul\Inventory\Database\Factories\OperationFactory;
use Webkul\Inventory\Database\Factories\OrderPointFactory;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Database\Factories\ProductSupplierFactory;
use Webkul\Product\Models\Product as BaseProduct;
use Webkul\Product\Models\ProductSupplier;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Currency;

class InventoryDemoSeeder extends Seeder
{
    private const DEMO_REFERENCE_PREFIX = 'DEMO-INV-';

    private const DEMO_RECEIPT_REFERENCE = 'DEMO-RCPT-001';

    public function run(): void
    {
        if (! Schema::hasTable('inventories_order_points') || ! Schema::hasTable('products_products')) {
            return;
        }

        $warehouse = Warehouse::query()->first();

        if (! $warehouse) {
            return;
        }

        $products = $this->seedProducts($warehouse);
        $this->seedSuppliers($products, $warehouse);
        $this->seedOrderPoints($warehouse, $products);
        $moves = $this->seedMoves($warehouse, $products);
        $this->seedConsumptionLogs($products, $moves);
        $this->seedPendingReceipt($warehouse, $products);
    }

    /**
     * @return array<int, BaseProduct>
     */
    protected function seedProducts(Warehouse $warehouse): array
    {
        if (BaseProduct::query()->where('reference', 'like', self::DEMO_REFERENCE_PREFIX.'%')->exists()) {
            return BaseProduct::query()
                ->where('reference', 'like', self::DEMO_REFERENCE_PREFIX.'%')
                ->get()
                ->all();
        }

        $names = [
            'en' => ['Office Paper Ream', 'Printer Toner', 'Cleaning Supplies Kit'],
            'ar' => ['رزمة ورق مكتبي', 'حبر طابعة', 'طقم مستلزمات تنظيف'],
        ];

        $locale = app()->getLocale();
        $labels = $names[$locale] ?? $names['en'];
        $products = [];

        foreach ($labels as $index => $label) {
            $products[] = Product::factory()->create([
                'name'        => $label,
                'reference'   => self::DEMO_REFERENCE_PREFIX.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'is_storable' => true,
                'company_id'  => $warehouse->company_id,
            ]);
        }

        return $products;
    }

    /**
     * @param  array<int, BaseProduct>  $products
     */
    protected function seedSuppliers(array $products, Warehouse $warehouse): void
    {
        if ($products === [] || ! Schema::hasTable('products_product_suppliers')) {
            return;
        }

        $partner = Partner::query()->where('supplier_rank', '>', 0)->first()
            ?? Partner::query()->first();

        if (! $partner) {
            return;
        }

        $currencyId = Currency::query()->value('id');

        foreach ($products as $index => $product) {
            $exists = ProductSupplier::query()
                ->where('product_id', $product->id)
                ->where('partner_id', $partner->id)
                ->exists();

            if ($exists) {
                continue;
            }

            ProductSupplierFactory::new()->create([
                'product_id'   => $product->id,
                'partner_id'   => $partner->id,
                'product_name' => $product->name,
                'product_code' => $product->reference,
                'price'        => 12.500 + ($index * 3.250),
                'currency_id'  => $currencyId,
                'company_id'   => $warehouse->company_id,
                'creator_id'   => User::query()->value('id') ?? 1,
            ]);
        }
    }

    /**
     * @param  array<int, BaseProduct>  $products
     */
    protected function seedOrderPoints(Warehouse $warehouse, array $products): void
    {
        if ($products === []) {
            return;
        }

        $locationId = $warehouse->lot_stock_location_id;

        if (! $locationId) {
            return;
        }

        foreach ($products as $product) {
            $exists = OrderPoint::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->exists();

            if ($exists) {
                continue;
            }

            OrderPointFactory::new()->create([
                'name'            => $product->name,
                'product_id'      => $product->id,
                'warehouse_id'    => $warehouse->id,
                'location_id'     => $locationId,
                'company_id'      => $warehouse->company_id,
                'product_min_qty' => 10,
                'product_max_qty' => 50,
                'qty_multiple'    => 1,
                'trigger'         => OrderPointTrigger::MANUAL,
            ]);
        }
    }

    /**
     * @param  array<int, BaseProduct>  $products
     * @return array<int, Move>
     */
    protected function seedMoves(Warehouse $warehouse, array $products): array
    {
        if ($products === [] || ! Schema::hasTable('inventories_moves')) {
            return [];
        }

        $locationId = $warehouse->lot_stock_location_id;

        if (! $locationId) {
            return [];
        }

        $consumptionLocationId = $this->resolveConsumptionLocationId($warehouse, $locationId);
        $moves = [];

        foreach ($products as $index => $product) {
            $reference = 'DEMO-MOV-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

            $existing = Move::query()->where('reference', $reference)->first();

            if ($existing) {
                $moves[] = $existing;

                continue;
            }

            $moves[] = MoveFactory::new()
                ->done()
                ->create([
                    'name'                    => __('inventory-extensions::dashboard.recent_movements').' '.$product->name,
                    'reference'               => $reference,
                    'product_id'              => $product->id,
                    'warehouse_id'            => $warehouse->id,
                    'source_location_id'      => $locationId,
                    'destination_location_id' => $consumptionLocationId,
                    'product_qty'             => 2.5,
                    'quantity'                => 2.5,
                    'product_uom_qty'         => 2.5,
                    'state'                   => MoveState::DONE,
                    'updated_at'              => now()->subDays($index + 1),
                    'company_id'              => $warehouse->company_id,
                ]);
        }

        return $moves;
    }

    /**
     * @param  array<int, BaseProduct>  $products
     * @param  array<int, Move>  $moves
     */
    protected function seedConsumptionLogs(array $products, array $moves): void
    {
        if ($products === [] || ! Schema::hasTable('inventory_consumption_logs')) {
            return;
        }

        $department = Department::query()->first();
        $userId = User::query()->value('id') ?? 1;

        foreach ($moves as $index => $move) {
            if (InventoryConsumptionLog::query()->where('move_id', $move->id)->exists()) {
                continue;
            }

            InventoryConsumptionLog::query()->create([
                'move_id'       => $move->id,
                'operation_id'  => $move->operation_id,
                'product_id'    => $move->product_id,
                'quantity'      => $move->product_qty,
                'department_id' => $department?->id,
                'purpose'       => __('inventory-extensions::consumption.demo_purpose'),
                'recorded_by'   => $userId,
                'company_id'    => $move->company_id,
            ]);
        }
    }

    /**
     * @param  array<int, BaseProduct>  $products
     */
    protected function seedPendingReceipt(Warehouse $warehouse, array $products): void
    {
        if ($products === [] || ! Schema::hasTable('inventories_operations')) {
            return;
        }

        if (Operation::query()->where('name', self::DEMO_RECEIPT_REFERENCE)->exists()) {
            return;
        }

        $product = $products[0] ?? null;

        if (! $product) {
            return;
        }

        OperationFactory::new()->receipt()->create([
            'name'       => self::DEMO_RECEIPT_REFERENCE,
            'state'      => OperationState::CONFIRMED,
            'company_id' => $warehouse->company_id,
            'creator_id' => User::query()->value('id') ?? 1,
        ]);
    }

    protected function resolveConsumptionLocationId(Warehouse $warehouse, int $fallbackLocationId): int
    {
        if (! Schema::hasTable('inventories_locations')) {
            return $fallbackLocationId;
        }

        $consumptionLocation = Location::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('name', __('inventory-extensions::locations.consumption'))
            ->value('id');

        return $consumptionLocation ?? $fallbackLocationId;
    }
}
