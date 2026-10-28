<?php

namespace Tests\Helpers;

use Webkul\Account\Models\Move;
use Webkul\Account\Models\Tax;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;

class SalesFixtures
{
    /**
     * @return array{0: Order, 1: Product, 2: int}
     */
    public static function confirmedSaleWithStock(float $onHand, float $ordered, bool $withUnstockedLine = false): array
    {
        $warehouse = Warehouse::query()->firstOrFail();

        $stockLocationId = $warehouse->lot_stock_location_id;

        $product = Product::factory()->create([
            'type'        => ProductType::GOODS,
            'is_storable' => true,
            'tracking'    => 'qty',
            'price'       => 2.35,
        ]);

        ProductQuantity::factory()->create([
            'product_id'        => $product->id,
            'location_id'       => $stockLocationId,
            'quantity'          => $onHand,
            'reserved_quantity' => 0,
            'company_id'        => null,
        ]);

        $order = Order::factory()->create([
            'state'        => OrderState::DRAFT,
            'warehouse_id' => $warehouse->id,
            'company_id'   => $warehouse->company_id,
        ]);

        OrderLine::factory()->create([
            'order_id'        => $order->id,
            'product_id'      => $product->id,
            'product_uom_id'  => $product->uom_id,
            'product_qty'     => $ordered,
            'product_uom_qty' => $ordered,
            'price_unit'      => 2.35,
            'customer_lead'   => 0,
            'company_id'      => $warehouse->company_id,
        ]);

        if ($withUnstockedLine) {
            $other = Product::factory()->create([
                'type'        => ProductType::GOODS,
                'is_storable' => true,
                'tracking'    => 'qty',
                'price'       => 3.5,
            ]);

            OrderLine::factory()->create([
                'order_id'        => $order->id,
                'product_id'      => $other->id,
                'product_uom_id'  => $other->uom_id,
                'product_qty'     => 5,
                'product_uom_qty' => 5,
                'price_unit'      => 3.5,
                'customer_lead'   => 0,
                'company_id'      => $warehouse->company_id,
            ]);
        }

        $order = SaleOrder::confirmSaleOrder($order->fresh());

        return [$order, $product, $stockLocationId];
    }

    /**
     * Confirm, deliver and invoice a 20 x 10.000 order with 5% VAT; return the order and its draft invoice.
     *
     * @return array{0: Order, 1: Move}
     */
    public static function deliveredAndInvoicedOrder(): array
    {
        [$order] = static::confirmedSaleWithStock(onHand: 30, ordered: 20);

        $order->lines()->firstOrFail()->update(['price_unit' => 10]);
        $order->lines->first()->taxes()->sync([Tax::query()->where('type_tax_use', 'sale')->value('id')]);

        Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

        SaleOrder::createInvoice(SaleOrder::computeSaleOrder($order->fresh()), ['advance_payment_method' => 'delivered']);

        return [$order->fresh(), $order->fresh()->accountMoves()->firstOrFail()];
    }
}
