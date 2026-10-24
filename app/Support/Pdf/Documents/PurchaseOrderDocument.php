<?php

namespace App\Support\Pdf\Documents;

use App\Support\OmrFormatter;
use Webkul\Purchase\Models\Order;

/**
 * Builds the printable request for quotation or purchase order as data for pdf.trade-document.
 */
class PurchaseOrderDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Order $record, bool $isRequestForQuotation): array
    {
        $money = new DocumentMoney($record->currency);

        $columns = [
            ['key' => 'index', 'label' => '#', 'width' => '4%'],
            ['key' => 'description', 'label' => __('Description')],
            ['key' => 'quantity', 'label' => __('Quantity'), 'width' => '13%', 'numeric' => true],
            ['key' => 'price', 'label' => __('Unit Price'), 'width' => '14%', 'numeric' => true],
            ['key' => 'discount', 'label' => __('Discount'), 'width' => '8%', 'numeric' => true],
            ['key' => 'taxes', 'label' => __('Taxes'), 'width' => '11%'],
            ['key' => 'total', 'label' => __('Amount'), 'width' => '15%', 'numeric' => true],
        ];

        $rows = $record->lines->values()->map(fn ($line, int $index): array => [
            'index'       => $index + 1,
            'description' => $line->name,
            'quantity'    => rtrim(rtrim(number_format((float) $line->product_qty, 4, '.', ''), '0'), '.').' '.($line->uom?->name ?? ''),
            'price'       => $money->number($line->price_unit),
            'discount'    => (float) $line->discount > 0 ? rtrim(rtrim(number_format((float) $line->discount, 2, '.', ''), '0'), '.').'%' : '',
            'taxes'       => $line->taxes->map(fn ($tax): string => $tax->amount_type?->value === 'percent'
                ? rtrim(rtrim(number_format((float) $tax->amount, 3, '.', ''), '0'), '.').'%'
                : (string) $tax->name)->implode('، '),
            'total'       => $money->number($line->price_subtotal),
        ])->all();

        $meta = array_values(array_filter([
            $record->user ? [__('Buyer'), $record->user->name] : null,
            $record->partner_reference ? [__('Order Reference'), $record->partner_reference] : null,
            $record->ordered_at ? [__('Order Deadline'), OmrFormatter::localizedDate($record->ordered_at)] : null,
            $record->planned_at ? [__('Expected Arrival'), OmrFormatter::localizedDate($record->planned_at)] : null,
            $record->paymentTerm?->name ? [__('Payment Terms:'), $record->paymentTerm->name] : null,
        ]));

        $notes = [];

        if ($isRequestForQuotation && $record->valid_until) {
            $notes[] = ['title' => __('Quotation Valid Until:'), 'text' => OmrFormatter::localizedDate($record->valid_until)];
        }

        return [
            'company'    => $record->company,
            'title'      => ($isRequestForQuotation ? __('Request for Quotation #') : __('Purchase Order #')).' '.$record->name,
            'partyLabel' => __('invoices-extensions::pdf.vendor'),
            'party'      => $record->partner,
            'meta'       => $meta,
            'columns'    => $columns,
            'rows'       => $rows,
            'totals'     => [
                [__('Untaxed Amount'), $money->format($record->untaxed_amount), ''],
                [__('Tax'), $money->format($record->tax_amount), ''],
                [__('Total'), $money->format($record->total_amount), 'grand'],
            ],
            'notes'   => $notes,
            'closing' => $isRequestForQuotation
                ? __('We look forward to your response. Please contact us for any clarifications.')
                : __('Thank you for your business! Please contact us if you have any questions.'),
        ];
    }
}
