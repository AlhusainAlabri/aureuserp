<?php

namespace App\Support\Pdf\Documents;

use App\Support\OmrFormatter;
use Webkul\Manufacturing\Models\Order;

/**
 * Builds the printable manufacturing order (components and operations) as data for pdf.trade-document.
 */
class ManufacturingOrderDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Order $record): array
    {
        $unit = $record->uom?->name ?? '';

        $components = $record->rawMaterialMoves->values()->map(fn ($move, int $index): array => [
            'index'    => $index + 1,
            'product'  => $move->product?->name,
            'toUse'    => static::trim($move->product_uom_qty).' '.($move->uom?->name ?? ''),
            'consumed' => static::trim($move->quantity ?? 0),
        ])->all();

        $operations = $record->workOrders->values()->map(fn ($workOrder, int $index): array => [
            'index'      => $index + 1,
            'operation'  => $workOrder->name ?? $workOrder->operation?->name,
            'workCenter' => $workOrder->workCenter?->name,
            'duration'   => number_format((float) ($workOrder->duration ?? 0), 1),
        ])->all();

        $meta = array_values(array_filter([
            [__('Product:'), $record->product?->name ?? '—'],
            [__('Quantity to Produce:'), static::trim($record->quantity).' '.$unit],
            $record->quantity_producing !== null ? [__('Quantity Producing:'), static::trim($record->quantity_producing).' '.$unit] : null,
            $record->assignedUser ? [__('Responsible:'), $record->assignedUser->name] : null,
            $record->started_at ? [__('Scheduled At:'), OmrFormatter::localizedDate($record->started_at)] : null,
        ]));

        return [
            'company'    => $record->company,
            'title'      => __('Manufacturing Order').' '.$record->name,
            'partyLabel' => '',
            'party'      => null,
            'meta'       => $meta,
            'columns'    => [],
            'rows'       => [],
            'tables'     => array_values(array_filter([
                $components === [] ? null : [
                    'title'   => __('Components'),
                    'columns' => [
                        ['key' => 'index', 'label' => '#', 'width' => '5%'],
                        ['key' => 'product', 'label' => __('Product')],
                        ['key' => 'toUse', 'label' => __('To Consume'), 'width' => '22%', 'numeric' => true],
                        ['key' => 'consumed', 'label' => __('Consumed'), 'width' => '18%', 'numeric' => true],
                    ],
                    'rows' => $components,
                ],
                $operations === [] ? null : [
                    'title'   => __('Operations Done'),
                    'columns' => [
                        ['key' => 'index', 'label' => '#', 'width' => '5%'],
                        ['key' => 'operation', 'label' => __('Operation')],
                        ['key' => 'workCenter', 'label' => __('WorkCenter'), 'width' => '28%'],
                        ['key' => 'duration', 'label' => __('Duration (minutes)'), 'width' => '20%', 'numeric' => true],
                    ],
                    'rows' => $operations,
                ],
            ])),
            'totals'  => [],
            'notes'   => [],
            'closing' => null,
        ];
    }

    protected static function trim(float|int|string|null $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
}
