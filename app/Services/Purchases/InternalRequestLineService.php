<?php

namespace App\Services\Purchases;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use Webkul\Purchase\Models\OrderLine;
use Webkul\Purchase\Models\PurchaseOrder;

class InternalRequestLineService
{
    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    public function syncFromFormData(PurchaseOrder $order, array $lineItems): void
    {
        if (! PurchaseOrderResourceExtensions::isInternalRequest($order->request_type)) {
            return;
        }

        $lineItems = array_values(array_filter(
            $lineItems,
            fn (array $line): bool => filled($line['name'] ?? null),
        ));

        if ($lineItems === []) {
            $lineItems = [$this->fallbackLineFromOrder($order)];
        }

        $order->lines()->delete();

        foreach ($lineItems as $lineItem) {
            $this->createLine($order, $lineItem);
        }

        $order->refresh();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function formStateFromOrder(PurchaseOrder $order): array
    {
        return $order->lines
            ->map(fn (OrderLine $line): array => [
                'name'             => $line->name,
                'product_qty'      => (float) $line->product_qty,
                'price_unit'       => (float) $line->price_unit,
                'product_uom_qty'  => (float) ($line->product_uom_qty ?? $line->product_qty),
                'price_subtotal'   => (float) $line->price_subtotal,
                'price_total'      => (float) $line->price_total,
                'price_tax'        => (float) ($line->price_tax ?? 0),
                'discount'         => (float) ($line->discount ?? 0),
                'product_id'       => $line->product_id,
                'uom_id'           => $line->uom_id,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $lineItem
     */
    protected function createLine(PurchaseOrder $order, array $lineItem): void
    {
        $quantity = floatval($lineItem['product_qty'] ?? 1);
        $priceUnit = floatval($lineItem['price_unit'] ?? 0);
        $totals = PurchaseOrderResourceExtensions::calculateInternalLineTotals($quantity, $priceUnit);

        OrderLine::query()->create([
            'order_id'         => $order->id,
            'product_id'       => $lineItem['product_id'] ?? PurchaseOrderResourceExtensions::defaultInternalLineProductId(),
            'uom_id'           => $lineItem['uom_id'] ?? PurchaseOrderResourceExtensions::defaultInternalLineUomId(),
            'name'             => (string) ($lineItem['name'] ?? $order->origin ?? __('purchases-extensions::request.lines.description')),
            'product_qty'      => $quantity,
            'product_uom_qty'  => floatval($lineItem['product_uom_qty'] ?? $quantity),
            'price_unit'       => $priceUnit,
            'price_subtotal'   => $lineItem['price_subtotal'] ?? $totals['price_subtotal'],
            'price_total'      => $lineItem['price_total'] ?? $totals['price_total'],
            'price_tax'        => $lineItem['price_tax'] ?? $totals['price_tax'],
            'discount'         => $lineItem['discount'] ?? 0,
            'qty_to_invoice'   => $quantity,
            'company_id'       => $order->company_id,
            'currency_id'      => $order->currency_id,
            'creator_id'       => $order->creator_id,
        ]);
    }

    /** @return array<string, mixed> */
    protected function fallbackLineFromOrder(PurchaseOrder $order): array
    {
        return [
            'name'        => filled($order->origin) ? $order->origin : __('purchases-extensions::request.lines.description'),
            'product_qty' => 1,
            'price_unit'  => floatval($order->total_amount ?? 0),
        ];
    }
}
