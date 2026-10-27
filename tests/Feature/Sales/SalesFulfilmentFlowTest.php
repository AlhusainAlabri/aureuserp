<?php

use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\SalesFixtures;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'sales'       => 'sales_orders',
        'inventories' => 'inventories_operations',
    ]);

    $this->actingAs(User::query()->firstOrFail());
});

it('reserves no more than what is on hand when a sales order is confirmed', function (): void {
    [$order, $product, $stockLocationId] = SalesFixtures::confirmedSaleWithStock(onHand: 10, ordered: 20);

    $reserved = (float) ProductQuantity::query()
        ->where('product_id', $product->id)
        ->where('location_id', $stockLocationId)
        ->sum('reserved_quantity');

    $move = Move::query()->where('product_id', $product->id)->firstOrFail();

    expect($reserved)->toBe(10.0)
        ->and((float) $move->lines()->sum('qty'))->toBe(10.0)
        ->and($move->state)->toBe(MoveState::PARTIALLY_ASSIGNED);
});

it('still reserves only the stock on hand when another line of the order has none', function (): void {
    [$order, $product, $stockLocationId] = SalesFixtures::confirmedSaleWithStock(onHand: 10, ordered: 20, withUnstockedLine: true);

    $reserved = (float) ProductQuantity::query()
        ->where('product_id', $product->id)
        ->where('location_id', $stockLocationId)
        ->sum('reserved_quantity');

    $move = Move::query()->where('product_id', $product->id)->firstOrFail();

    expect($reserved)->toBe(10.0)
        ->and((float) $move->lines()->sum('qty'))->toBe(10.0);
});

it('refuses to validate a delivery that would take more than is on hand', function (): void {
    [$order, $product, $stockLocationId] = SalesFixtures::confirmedSaleWithStock(onHand: 10, ordered: 20);

    $operation = $order->operations()->firstOrFail();

    $operation->moveLines()->update(['qty' => 20]);

    expect(fn () => Inventory::doneTransfer($operation->fresh(), cancelBackOrder: true))
        ->toThrow(Exception::class, '10');

    expect((float) ProductQuantity::query()
        ->where('product_id', $product->id)
        ->where('location_id', $stockLocationId)
        ->sum('quantity'))->toBe(10.0);
});

it('validates a delivery that fits what is on hand', function (): void {
    [$order, $product, $stockLocationId] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    $operation = Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

    expect((float) ProductQuantity::query()
        ->where('product_id', $product->id)
        ->where('location_id', $stockLocationId)
        ->sum('quantity'))->toBe(10.0)
        ->and($order->fresh()->lines->first()->qty_delivered)->toEqual(20);
});

it('leaves lines that have nothing delivered off the invoice and keeps the order open', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20, withUnstockedLine: true);

    Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: false);

    $order = $order->fresh();

    SaleOrder::createInvoice($order, ['advance_payment_method' => 'delivered']);

    $invoice = $order->fresh()->accountMoves()->firstOrFail();

    expect($invoice->lines()->where('quantity', '<=', 0)->count())->toBe(0)
        ->and($invoice->lines()->whereNotNull('product_id')->count())->toBe(1)
        ->and($order->fresh()->invoice_status->value)->toBe('no');
});

it('keeps earlier invoices linked when a line is invoiced a second time', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    $line = $order->lines()->firstOrFail();

    $line->update(['qty_to_invoice' => 8, 'qty_delivered' => 8]);

    SaleOrder::createInvoice($order->fresh(), ['advance_payment_method' => 'delivered']);

    $line->update(['qty_to_invoice' => 6, 'qty_delivered' => 14]);

    SaleOrder::createInvoice($order->fresh(), ['advance_payment_method' => 'delivered']);

    expect($line->fresh()->accountMoveLines()->count())->toBe(2);
});

it('lowers the delivered quantity when goods come back and the return updates the order', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    $delivery = Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

    $deliveredMove = $delivery->moves()->firstOrFail();

    $return = Inventory::returnTransfer($delivery, [$deliveredMove->id => 5.0], toRefund: true);

    Inventory::doneTransfer($return->fresh(), cancelBackOrder: true);

    $line = $order->fresh()->lines->first();

    expect((float) $line->qty_delivered)->toBe(15.0)
        ->and((float) $line->qty_to_invoice)->toBe(15.0);
});

it('leaves the delivered quantity alone when the return is not meant to update the order', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    $delivery = Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

    $return = Inventory::returnTransfer($delivery, [$delivery->moves()->firstOrFail()->id => 5.0], toRefund: false);

    Inventory::doneTransfer($return->fresh(), cancelBackOrder: true);

    expect((float) $order->fresh()->lines->first()->qty_delivered)->toBe(20.0);
});
