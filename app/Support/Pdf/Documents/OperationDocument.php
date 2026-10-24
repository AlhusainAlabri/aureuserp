<?php

namespace App\Support\Pdf\Documents;

use App\Support\OmrFormatter;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Settings\TraceabilitySettings;

/**
 * Builds the printable delivery slip or packing slip (no prices) as data for pdf.trade-document.
 */
class OperationDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Operation $record, bool $isDeliverySlip): array
    {
        $traceability = app(TraceabilitySettings::class);
        $showLots = $traceability->enable_lots_serial_numbers && ($isDeliverySlip ? $traceability->display_on_delivery_slips : true);

        $columns = array_values(array_filter([
            ['key' => 'index', 'label' => '#', 'width' => '5%'],
            ['key' => 'product', 'label' => __('Product')],
            $showLots ? ['key' => 'lot', 'label' => __('Lot/Serial Number'), 'width' => '22%'] : null,
            ['key' => 'quantity', 'label' => __('Quantity'), 'width' => '18%', 'numeric' => true],
            $isDeliverySlip ? null : ['key' => 'barcode', 'label' => __('Product Barcode'), 'width' => '20%'],
        ]));

        $rows = $record->moveLines->values()->map(fn ($line, int $index): array => [
            'index'    => $index + 1,
            'product'  => $line->product?->name,
            'lot'      => $line->lot?->name,
            'quantity' => rtrim(rtrim(number_format((float) $line->qty, 4, '.', ''), '0'), '.').' '.($line->uom?->name ?? ''),
            'barcode'  => $line->product?->barcode,
        ])->all();

        $meta = array_values(array_filter([
            $record->scheduled_at ? [$isDeliverySlip ? __('Shipping Date:') : __('Scheduled At:'), OmrFormatter::localizedDate($record->scheduled_at)] : null,
            $record->origin ? [__('Source'), $record->origin] : null,
            $isDeliverySlip ? null : [__('Status:'), $record->state?->getLabel() ?? ''],
        ]));

        return [
            'company'    => $record->company,
            'title'      => ($isDeliverySlip ? __('Delivery Slip #') : __('Packing Slip #')).' '.$record->name,
            'partyLabel' => $isDeliverySlip ? __('Delivery Address') : __('Vendor Address'),
            'party'      => $record->partner,
            'meta'       => $meta,
            'columns'    => $columns,
            'rows'       => $rows,
            'totals'     => [],
            'notes'      => [],
            'closing'    => $isDeliverySlip
                ? __('Please inspect all items upon delivery. Report any discrepancies within 24 hours.')
                : __('All items have been checked and packed according to quality standards.'),
        ];
    }
}
