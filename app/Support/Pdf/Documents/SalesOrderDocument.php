<?php

namespace App\Support\Pdf\Documents;

use App\Support\OmrFormatter;
use Webkul\Product\Settings\ProductSettings;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;

/**
 * Builds the printable quotation or sales order as data for pdf.trade-document.
 */
class SalesOrderDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Order $record): array
    {
        $money = new DocumentMoney($record->currency);
        $showUnits = app(ProductSettings::class)->enable_uom;
        $isOrder = $record->state === OrderState::SALE;

        $lines = $record->lines->filter(fn ($line): bool => ! $line->display_type)->values();
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
            'quantity' => static::trim($line->product_uom_qty ?? $line->product_qty, 4),
            'unit'     => $line->uom?->name ?? $line->product?->uom?->name,
            'price'    => $money->number($line->price_unit),
            'discount' => (float) $line->discount > 0 ? static::trim($line->discount, 2).'%' : '',
            'taxes'    => $line->taxes->map(fn ($tax): string => $tax->amount_type?->value === 'percent'
                ? static::trim($tax->amount, 3).'%'
                : (string) $tax->name)->implode('، '),
            'total'    => $money->number($line->price_subtotal),
        ])->all();

        $meta = array_values(array_filter([
            $record->date_order ? [__('sales-extensions::pdf.date', ['type' => $isOrder ? __('sales-extensions::pdf.order') : __('sales-extensions::pdf.quotation')]), OmrFormatter::localizedDate($record->date_order)] : null,
            $record->validity_date ? [__('sales-extensions::pdf.expiration-date'), OmrFormatter::localizedDate($record->validity_date)] : null,
            $record->client_order_ref ? [__('Reference'), $record->client_order_ref] : null,
            $record->paymentTerm?->name ? [__('invoices-extensions::pdf.payment-terms'), $record->paymentTerm->name] : null,
            $record->user ? [__('Salesperson'), $record->user->name] : null,
        ]));

        $notes = [];

        if (filled(strip_tags((string) $record->note))) {
            $notes[] = ['title' => __('Terms & Conditions:'), 'text' => trim(html_entity_decode(strip_tags((string) $record->note)))];
        }

        return [
            'company'    => $record->company,
            'title'      => ($isOrder ? __('sales-extensions::pdf.order') : __('sales-extensions::pdf.quotation')).' '.$record->name,
            'partyLabel' => __('invoices-extensions::pdf.customer'),
            'party'      => $record->partner,
            'meta'       => $meta,
            'columns'    => $columns,
            'rows'       => $rows,
            'totals'     => [
                [__('invoices-extensions::pdf.subtotal'), $money->format($record->amount_untaxed), ''],
                [__('invoices-extensions::pdf.tax'), $money->format($record->amount_tax), ''],
                [__('invoices-extensions::pdf.grand-total'), $money->format($record->amount_total), 'grand'],
            ],
            'notes'   => $notes,
            'closing' => null,
        ];
    }

    protected static function trim(float|int|string|null $value, int $places): string
    {
        return rtrim(rtrim(number_format((float) $value, $places, '.', ''), '0'), '.');
    }
}
