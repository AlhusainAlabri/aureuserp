<?php

namespace App\Services\Sales;

use Exception;
use Illuminate\Support\Collection;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Move as AccountMove;
use Webkul\Account\Models\MoveLine;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Models\Operation;
use Webkul\Sale\Facades\SaleOrder as SaleOrderFacade;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;

/**
 * Builds the draft credit note for goods a customer sent back.
 *
 * Only returns made with "update the order" switched on count. For every order line the
 * quantity still to credit is what came back minus what earlier credit notes already cover,
 * and it is credited against the posted invoice(s) that billed the line.
 */
class ReturnCreditNote
{
    /**
     * Whether the return is a finished customer return with something left to credit.
     */
    public function canCreate(Operation $return): bool
    {
        return $this->creditableLines($return)->isNotEmpty();
    }

    /**
     * @return Collection<int, array{order_line: OrderLine, invoice_line: MoveLine, quantity: float}>
     */
    public function creditableLines(Operation $return): Collection
    {
        if (! $return->return_id || ! $return->sale_order_id || $return->state !== OperationState::DONE) {
            return collect();
        }

        $order = $return->saleOrder;

        $allocations = collect();

        foreach ($order->lines()->with(['accountMoveLines.move', 'uom'])->get() as $line) {
            $remaining = $this->returnedQuantity($line) - $this->creditedQuantity($line);

            if (float_compare($remaining, 0.0, precisionDigits: 4) <= 0) {
                continue;
            }

            $creditedByInvoice = $this->creditedByInvoice($line);

            $invoiceLines = $line->accountMoveLines
                ->filter(fn (MoveLine $moveLine) => $moveLine->move->move_type === MoveType::OUT_INVOICE && $moveLine->move->state === MoveState::POSTED)
                ->sortBy('id');

            foreach ($invoiceLines as $invoiceLine) {
                if (float_compare($remaining, 0.0, precisionDigits: 4) <= 0) {
                    break;
                }

                $available = (float) $invoiceLine->quantity - ($creditedByInvoice[$invoiceLine->move_id] ?? 0.0);
                $take = min($remaining, $available);

                if (float_compare($take, 0.0, precisionDigits: 4) <= 0) {
                    continue;
                }

                $allocations->push(['order_line' => $line, 'invoice_line' => $invoiceLine, 'quantity' => $take]);

                $remaining -= $take;
            }
        }

        return $allocations;
    }

    /**
     * Create one draft credit note per invoice that has something to credit.
     *
     * @return Collection<int, AccountMove>
     *
     * @throws Exception
     */
    public function create(Operation $return): Collection
    {
        $allocations = $this->creditableLines($return);

        if ($allocations->isEmpty()) {
            throw new Exception(__('sales-extensions::messages.credit-note-from-return.nothing'));
        }

        $order = $return->saleOrder;

        $creditNotes = $allocations
            ->groupBy(fn (array $allocation) => $allocation['invoice_line']->move_id)
            ->map(fn (Collection $group) => $this->createCreditNote($order, $return, $group));

        SaleOrderFacade::computeSaleOrder($order->refresh());

        return $creditNotes->values();
    }

    /**
     * @param  Collection<int, array{order_line: OrderLine, invoice_line: MoveLine, quantity: float}>  $group
     */
    protected function createCreditNote(Order $order, Operation $return, Collection $group): AccountMove
    {
        /** @var AccountMove $invoice */
        $invoice = $group->first()['invoice_line']->move;

        $creditNote = AccountMove::create([
            'move_type'          => MoveType::OUT_REFUND,
            'reversed_entry_id'  => $invoice->id,
            'invoice_origin'     => $order->name,
            'reference'          => __('sales-extensions::messages.credit-note-from-return.reference', ['return' => $return->name, 'invoice' => $invoice->name]),
            'date'               => now(),
            'invoice_date'       => now(),
            'invoice_date_due'   => now(),
            'company_id'         => $invoice->company_id,
            'currency_id'        => $invoice->currency_id,
            'journal_id'         => $invoice->journal_id,
            'partner_id'         => $invoice->partner_id,
            'fiscal_position_id' => $invoice->fiscal_position_id,
            'invoice_user_id'    => $invoice->invoice_user_id,
        ]);

        $order->accountMoves()->attach($creditNote->id);

        foreach ($group as $allocation) {
            /** @var MoveLine $source */
            $source = $allocation['invoice_line'];

            $line = $creditNote->lines()->create([
                'name'         => $source->name,
                'date'         => $creditNote->date,
                'creator_id'   => $creditNote->creator_id,
                'parent_state' => $creditNote->state,
                'quantity'     => $allocation['quantity'],
                'price_unit'   => $source->price_unit,
                'discount'     => $source->discount,
                'currency_id'  => $creditNote->currency_id,
                'product_id'   => $source->product_id,
                'uom_id'       => $source->uom_id,
            ]);

            $line->taxes()->sync($source->taxes->pluck('id'));

            $allocation['order_line']->accountMoveLines()->syncWithoutDetaching([$line->id]);
        }

        $creditNote = AccountFacade::computeAccountMove($creditNote);

        $creditNote->addMessage([
            'body' => __('sales-extensions::messages.credit-note-from-return.chatter', ['return' => $return->name]),
            'type' => 'comment',
        ]);

        return $creditNote;
    }

    /**
     * Quantity that came back with "update the order" switched on, in the order line's unit.
     */
    protected function returnedQuantity(OrderLine $line): float
    {
        [, $incoming] = SaleOrderFacade::getOutgoingIncomingMoves($line);

        return (float) $incoming
            ->filter(fn ($move) => $move->state->value === 'done')
            ->sum(fn ($move) => $move->uom->computeQuantity($move->quantity, $line->uom, true, 'HALF-UP'));
    }

    /**
     * Quantity already covered by credit notes that are not cancelled.
     */
    protected function creditedQuantity(OrderLine $line): float
    {
        return (float) $this->liveCreditLines($line)->sum('quantity');
    }

    /**
     * @return array<int, float> credited quantity by the invoice it reverses
     */
    protected function creditedByInvoice(OrderLine $line): array
    {
        return $this->liveCreditLines($line)
            ->groupBy(fn (MoveLine $moveLine) => $moveLine->move->reversed_entry_id)
            ->map(fn (Collection $lines) => (float) $lines->sum('quantity'))
            ->all();
    }

    /**
     * @return Collection<int, MoveLine>
     */
    protected function liveCreditLines(OrderLine $line): Collection
    {
        return $line->accountMoveLines->filter(
            fn (MoveLine $moveLine) => $moveLine->display_type === DisplayType::PRODUCT
                && $moveLine->move->move_type === MoveType::OUT_REFUND
                && $moveLine->move->state !== MoveState::CANCEL
        );
    }
}
