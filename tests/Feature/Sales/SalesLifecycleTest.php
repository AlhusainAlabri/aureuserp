<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\SalesFixtures;
use Webkul\Account\Models\Tax;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions\CancelQuotationAction;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages\EditQuotation;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Settings\QuotationAndOrderSettings;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'sales'       => 'sales_orders',
        'inventories' => 'inventories_operations',
    ]);

    $this->actingAs(User::query()->firstOrFail());
});

it('cancels the delivery and frees the reserved stock when an order with nothing delivered is cancelled', function (): void {
    [$order, $product, $stockLocationId] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    $order = SaleOrder::cancelSaleOrder($order->fresh());

    expect($order->state)->toBe(OrderState::CANCEL)
        ->and($order->operations()->first()->state)->toBe(OperationState::CANCELED)
        ->and((float) ProductQuantity::query()
            ->where('product_id', $product->id)
            ->where('location_id', $stockLocationId)
            ->sum('reserved_quantity'))->toBe(0.0);
});

it('refuses to cancel an order once goods have been delivered', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

    expect(fn () => SaleOrder::cancelSaleOrder($order->fresh()))->toThrow(Exception::class);

    expect($order->fresh()->state)->toBe(OrderState::SALE);
});

it('refuses to cancel an order that has a posted invoice', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

    SaleOrder::createInvoice($order->fresh(), ['advance_payment_method' => 'delivered']);

    $invoice = $order->fresh()->accountMoves()->firstOrFail();

    $invoice->updateQuietly(['state' => 'posted']);

    expect(fn () => SaleOrder::cancelSaleOrder($order->fresh()))->toThrow(Exception::class);
});

it('reserves stock again when a cancelled order goes back to a quotation and is re-confirmed', function (): void {
    [$order, $product, $stockLocationId] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    $order = SaleOrder::cancelSaleOrder($order->fresh());
    $order = SaleOrder::backToQuotation($order->fresh());

    expect($order->state)->toBe(OrderState::DRAFT);

    $order = SaleOrder::confirmSaleOrder($order->fresh());

    $openOperations = $order->operations()->where('state', '!=', OperationState::CANCELED->value)->get();

    expect($openOperations)->toHaveCount(1)
        ->and((float) ProductQuantity::query()
            ->where('product_id', $product->id)
            ->where('location_id', $stockLocationId)
            ->sum('reserved_quantity'))->toBe(20.0);
});

it('cancels a draft invoice together with its order', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    $line = $order->lines()->firstOrFail();

    $line->update(['qty_to_invoice' => 8, 'qty_delivered' => 8]);

    SaleOrder::createInvoice($order->fresh(), ['advance_payment_method' => 'delivered']);

    $invoice = $order->fresh()->accountMoves()->firstOrFail();

    expect($invoice->state->value)->toBe('draft');

    $line->update(['qty_delivered' => 0]);

    SaleOrder::cancelSaleOrder($order->fresh());

    expect($invoice->fresh()->state->value)->toBe('cancel');
});

it('keeps order and invoice totals equal when a line has a discount and VAT', function (): void {
    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 10);

    $line = $order->lines()->firstOrFail();

    $line->update(['price_unit' => 10, 'discount' => 10]);

    Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

    $line->taxes()->sync([Tax::query()->where('type_tax_use', 'sale')->value('id')]);

    $order = SaleOrder::computeSaleOrder($order->fresh());

    expect((float) $order->amount_untaxed)->toBe(90.0)
        ->and((float) $order->amount_tax)->toBe(4.5)
        ->and((float) $order->amount_total)->toBe(94.5);

    SaleOrder::createInvoice($order->fresh(), ['advance_payment_method' => 'delivered']);

    $invoice = $order->fresh()->accountMoves()->firstOrFail();

    expect((float) $invoice->amount_untaxed)->toBe(90.0)
        ->and((float) $invoice->amount_tax)->toBe(4.5)
        ->and((float) $invoice->amount_total)->toBe(94.5);
});

it('marks a draft quotation as sent once the email went out', function (): void {
    Mail::fake();

    $order = Order::factory()->create(['state' => OrderState::DRAFT]);
    $order->partner->update(['email' => 'buyer@example.test']);

    $result = SaleOrder::sendByEmail($order, [
        'partners'    => [$order->partner_id],
        'subject'     => 'عرض سعر',
        'description' => 'مرفق عرض السعر',
        'file'        => 'sales/2026/test.pdf',
    ]);

    expect($result['sent'])->toHaveCount(1)
        ->and($order->fresh()->state)->toBe(OrderState::SENT);
});

it('cancels a quotation even when the customer has no email address', function (): void {
    Mail::fake();

    $order = Order::factory()->create(['state' => OrderState::DRAFT]);
    $order->partner->update(['email' => null]);

    $order = SaleOrder::cancelSaleOrder($order, [
        'partners'    => [$order->partner_id],
        'subject'     => 'إلغاء',
        'description' => 'تم إلغاء العرض',
    ]);

    expect($order->state)->toBe(OrderState::CANCEL);
});

it('still creates the delivery when confirmed orders are locked by the settings', function (): void {
    $settings = app(QuotationAndOrderSettings::class);
    $settings->enable_lock_confirm_sales = true;
    $settings->save();

    try {
        [$order, $product, $stockLocationId] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

        expect((bool) $order->fresh()->locked)->toBeTrue()
            ->and($order->operations()->count())->toBe(1)
            ->and((float) ProductQuantity::query()
                ->where('product_id', $product->id)
                ->where('location_id', $stockLocationId)
                ->sum('reserved_quantity'))->toBe(20.0);
    } finally {
        $settings->enable_lock_confirm_sales = false;
        $settings->save();
    }
});

it('shows a message instead of an error when cancelling an order that has delivered goods', function (): void {
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
    Gate::before(fn (): bool => true);

    [$order] = SalesFixtures::confirmedSaleWithStock(onHand: 30, ordered: 20);

    Inventory::doneTransfer($order->operations()->firstOrFail()->fresh(), cancelBackOrder: true);

    Livewire\Livewire::test(EditQuotation::class, ['record' => $order->id])
        ->callAction(CancelQuotationAction::class, arguments: ['cancel' => true])
        ->assertNotified();

    expect($order->fresh()->state)->toBe(OrderState::SALE);
});
