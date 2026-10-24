<?php

namespace App\Support\Pdf\Documents;

use App\Support\OmrFormatter;
use Webkul\Account\Enums\AmountType;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Models\Move;
use Webkul\Product\Settings\ProductSettings;

/**
 * Builds the printable invoice, vendor bill, credit note or refund as data for pdf.trade-document.
 */
class AccountMoveDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Move $record, string $type): array
    {
        $money = new DocumentMoney($record->currency);
        $showUnits = app(ProductSettings::class)->enable_uom;

        $lines = $record->invoiceLines
            ->filter(fn ($line): bool => $line->display_type === null || $line->display_type === DisplayType::PRODUCT)
            ->values();

        $hasDiscount = $lines->contains(fn ($line): bool => (float) $line->discount > 0);

        $columns = array_values(array_filter([
            ['key' => 'index', 'label' => '#', 'width' => '4%'],
            ['key' => 'product', 'label' => __('invoices-extensions::pdf.product')],
            ['key' => 'quantity', 'label' => __('invoices-extensions::pdf.quantity'), 'width' => '10%', 'numeric' => true],
            $showUnits ? ['key' => 'unit', 'label' => __('invoices-extensions::pdf.unit'), 'width' => '9%'] : null,
            ['key' => 'price', 'label' => __('invoices-extensions::pdf.unit-price'), 'width' => '14%', 'numeric' => true],
            $hasDiscount ? ['key' => 'discount', 'label' => __('invoices-extensions::pdf.discount'), 'width' => '8%', 'numeric' => true] : null,
            ['key' => 'taxes', 'label' => __('invoices-extensions::pdf.taxes'), 'width' => '11%'],
            ['key' => 'total', 'label' => __('invoices-extensions::pdf.line-total'), 'width' => '15%', 'numeric' => true],
        ]));

        $rows = $lines->map(fn ($line, int $index): array => [
            'index'    => $index + 1,
            'product'  => $line->product?->name ?? $line->name,
            'quantity' => static::trim($line->quantity, 4),
            'unit'     => $line->uom?->name ?? $line->product?->uom?->name,
            'price'    => $money->number($line->price_unit),
            'discount' => (float) $line->discount > 0 ? static::trim($line->discount, 2).'%' : '',
            'taxes'    => static::taxes($line->taxes),
            'total'    => $money->number($line->price_subtotal),
        ])->all();

        $due = (float) ($record->amount_residual ?? 0);
        $total = (float) $record->amount_total;
        $isPosted = $record->state === MoveState::POSTED;

        $totals = array_values(array_filter([
            [__('invoices-extensions::pdf.subtotal'), $money->format($record->amount_untaxed), ''],
            [__('invoices-extensions::pdf.tax'), $money->format($record->amount_tax), ''],
            [__('invoices-extensions::pdf.grand-total'), $money->format($total), 'grand'],
            $isPosted && $due > 0 && $due < $total ? [__('invoices-extensions::pdf.paid'), $money->format($total - $due), ''] : null,
            $isPosted && $due > 0 ? [__('invoices-extensions::pdf.amount-due'), $money->format($due), 'due'] : null,
        ]));

        $meta = array_values(array_filter([
            $record->invoice_date ? [__("invoices-extensions::pdf.{$type}-date"), OmrFormatter::localizedDate($record->invoice_date)] : null,
            $record->invoice_date_due ? [__('invoices-extensions::pdf.due-date'), OmrFormatter::localizedDate($record->invoice_date_due)] : null,
            $record->ref ? [__('invoices-extensions::pdf.source'), $record->ref] : null,
            $record->invoicePaymentTerm?->name ? [__('invoices-extensions::pdf.payment-terms'), $record->invoicePaymentTerm->name] : null,
        ]));

        $notes = [];

        if ($record->name) {
            $payment = __('invoices-extensions::pdf.payment-communication', ['reference' => $record->name]);

            if ($record->partnerBank?->bank?->name || $record->partnerBank?->account_number) {
                $payment .= ' — '.__('invoices-extensions::pdf.account-details').' '
                    .($record->partnerBank?->bank?->name ?? __('invoices-extensions::pdf.not-available'))
                    .' ('.($record->partnerBank?->account_number ?? __('invoices-extensions::pdf.not-available')).')';
            }

            $notes[] = ['title' => __('invoices-extensions::pdf.payment-information'), 'text' => $payment];
        }

        return [
            'company'    => $record->company,
            'title'      => __("invoices-extensions::pdf.{$type}").' '.$record->name,
            'partyLabel' => __('invoices-extensions::pdf.'.(in_array($type, ['bill', 'refund'], true) ? 'vendor' : 'customer')),
            'party'      => $record->partner,
            'meta'       => $meta,
            'columns'    => $columns,
            'rows'       => $rows,
            'totals'     => $totals,
            'notes'      => $notes,
            'closing'    => null,
        ];
    }

    protected static function trim(float|int|string|null $value, int $places): string
    {
        return rtrim(rtrim(number_format((float) $value, $places, '.', ''), '0'), '.');
    }

    protected static function taxes($taxes): string
    {
        return $taxes->map(fn ($tax): string => $tax->amount_type === AmountType::PERCENT
            ? static::trim($tax->amount, 3).'%'
            : (string) $tax->name)->implode('، ');
    }
}
