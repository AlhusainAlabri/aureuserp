<?php

use App\Filament\Actions\Sales\CreateCreditNoteFromReturnAction;
use App\Services\Sales\ReturnCreditNote;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\SalesFixtures;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Move;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReceiptResource\Pages\ViewReceipt;
use Webkul\Inventory\Models\Operation;
use Webkul\Sale\Models\Order;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'sales'       => 'sales_orders',
        'inventories' => 'inventories_operations',
    ]);

    $this->actingAs(User::query()->firstOrFail());
});

/**
 * A posted invoice for 20 units, then a finished return of $quantity units.
 *
 * @return array{0: Order, 1: Move, 2: Operation}
 */
function postedInvoiceWithReturn(float $quantity, bool $updateOrder = true): array
{
    [$order, $invoice] = SalesFixtures::deliveredAndInvoicedOrder();

    $invoice = AccountFacade::confirmMove($invoice)->refresh();

    return [$order->fresh(), $invoice, finishedReturn($order->fresh(), $quantity, $updateOrder)];
}

function finishedReturn(Order $order, float $quantity, bool $updateOrder = true): Operation
{
    $delivery = $order->operations()->where('state', 'done')->firstOrFail();

    $return = Inventory::returnTransfer($delivery, [$delivery->moves()->firstOrFail()->id => $quantity], toRefund: $updateOrder);

    return Inventory::doneTransfer($return->fresh(), cancelBackOrder: true);
}

it('creates a draft credit note for exactly the returned quantity, against the invoice', function (): void {
    [$order, $invoice, $return] = postedInvoiceWithReturn(5);

    $creditNotes = app(ReturnCreditNote::class)->create($return);

    expect($creditNotes)->toHaveCount(1);

    $creditNote = $creditNotes->first()->refresh();

    expect($creditNote->move_type)->toBe(MoveType::OUT_REFUND)
        ->and($creditNote->state)->toBe(MoveState::DRAFT)
        ->and($creditNote->reversed_entry_id)->toBe($invoice->id)
        ->and((float) $creditNote->invoiceLines()->sum('quantity'))->toBe(5.0)
        ->and((float) $creditNote->amount_untaxed)->toBe(50.0)
        ->and((float) $creditNote->amount_tax)->toBe(2.5)
        ->and((float) $creditNote->amount_total)->toBe(52.5);
});

it('links the credit note to the order so the invoiced quantity follows it', function (): void {
    [$order, , $return] = postedInvoiceWithReturn(5);

    $creditNote = app(ReturnCreditNote::class)->create($return)->first();

    $line = $order->fresh()->lines->first();

    expect($order->accountMoves()->pluck('accounts_account_moves.id')->all())->toContain($creditNote->id)
        ->and((float) $line->qty_invoiced)->toBe(15.0)
        ->and((float) $line->qty_delivered)->toBe(15.0)
        ->and((float) $line->qty_to_invoice)->toBe(0.0);
});

it('reconciles the credit note against the invoice when it is posted', function (): void {
    [, $invoice, $return] = postedInvoiceWithReturn(5);

    $creditNote = AccountFacade::confirmMove(app(ReturnCreditNote::class)->create($return)->first())->refresh();

    expect($creditNote->name)->toMatch('#^RINV/\d{4}/\d+$#')
        ->and((float) $invoice->fresh()->amount_residual)->toBe(210.0 - 52.5);
});

it('does not credit the same goods twice but credits a later return', function (): void {
    [$order, , $return] = postedInvoiceWithReturn(5);

    $service = app(ReturnCreditNote::class);
    $service->create($return);

    expect($service->canCreate($return->fresh()))->toBeFalse()
        ->and(fn () => $service->create($return->fresh()))->toThrow(Exception::class);

    $second = finishedReturn($order->fresh(), 3);

    $creditNote = $service->create($second)->first();

    expect((float) $creditNote->invoiceLines()->sum('quantity'))->toBe(3.0);
});

it('offers nothing when the return did not update the order or is not finished', function (): void {
    [$order, , $notUpdating] = postedInvoiceWithReturn(5, updateOrder: false);

    expect(app(ReturnCreditNote::class)->canCreate($notUpdating))->toBeFalse();

    $delivery = $order->operations()->where('state', 'done')->firstOrFail();
    $open = Inventory::returnTransfer($delivery, [$delivery->moves()->firstOrFail()->id => 2], toRefund: true);

    expect(app(ReturnCreditNote::class)->canCreate($open->fresh()))->toBeFalse();
});

it('shows the create credit note button on a finished refunding return only', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Gate::before(fn (): bool => true);

    [, , $return] = postedInvoiceWithReturn(5);

    Livewire::test(ViewReceipt::class, ['record' => $return->id])
        ->assertActionVisible(CreateCreditNoteFromReturnAction::class)
        ->callAction(CreateCreditNoteFromReturnAction::class)
        ->assertNotified();

    Livewire::test(ViewReceipt::class, ['record' => $return->id])
        ->assertActionHidden(CreateCreditNoteFromReturnAction::class);
});
