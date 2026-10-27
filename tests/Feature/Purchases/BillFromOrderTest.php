<?php

use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Facades\PurchaseOrder as PurchaseOrderFacade;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Models\OrderLine;
use Webkul\Purchase\PurchaseOrder as PurchaseOrderService;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'purchases' => 'purchases_orders',
    ]);
});

it('leaves lines that are already billed in full off the next bill', function (): void {
    $order = Order::factory()->create();

    $open = OrderLine::factory()->create([
        'order_id'       => $order->id,
        'product_qty'    => 20,
        'qty_received'   => 20,
        'qty_invoiced'   => 12,
        'qty_to_invoice' => 8,
    ]);

    OrderLine::factory()->create([
        'order_id'       => $order->id,
        'product_qty'    => 10,
        'qty_received'   => 10,
        'qty_invoiced'   => 10,
        'qty_to_invoice' => 0,
    ]);

    AccountFacade::shouldReceive('computeAccountMove')->once();

    $billedLineIds = [];

    $service = Mockery::mock(PurchaseOrderService::class)->makePartial();
    $service->shouldReceive('createAccountMoveLine')
        ->andReturnUsing(function ($move, $line) use (&$billedLineIds): void {
            $billedLineIds[] = $line->id;
        });

    $service->createAccountMove($order->fresh());

    expect($billedLineIds)->toBe([$open->id]);
});

it('refuses to confirm an order that has no product lines', function (): void {
    $order = Order::factory()->create(['state' => OrderState::DRAFT]);

    expect(fn () => PurchaseOrderFacade::confirmPurchaseOrder($order))
        ->toThrow(Exception::class);

    expect($order->fresh()->state)->toBe(OrderState::DRAFT);
});
