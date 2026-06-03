<?php

namespace App\Services\Inventory;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Models\Move;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductSupplier;
use Webkul\Purchase\Models\OrderLine;

class ProductPurchaseHistoryService
{
    /**
     * @return Collection<int, array{
     *     supplier_id: int|null,
     *     supplier_name: string,
     *     last_purchase_date: Carbon|null,
     *     last_price: float,
     *     total_qty: float,
     *     currency_id: int|null,
     *     is_catalog_supplier: bool
     * }>
     */
    public function historyForProduct(Product $product, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= now()->subYear()->startOfDay();
        $to ??= now()->endOfDay();

        $received = collect($this->receivedPurchases($product, $from, $to));
        $catalog = collect($this->catalogSuppliers($product));

        return $received
            ->merge($catalog)
            ->groupBy('supplier_id')
            ->map(function (Collection $rows): array {
                $latest = $rows->sortByDesc(fn (array $row): int => $row['last_purchase_date']?->timestamp ?? 0)->first();

                return [
                    'supplier_id'        => $latest['supplier_id'],
                    'supplier_name'      => $latest['supplier_name'],
                    'last_purchase_date' => $latest['last_purchase_date'],
                    'last_price'         => (float) $latest['last_price'],
                    'total_qty'          => (float) $rows->sum('total_qty'),
                    'currency_id'        => $latest['currency_id'],
                    'is_catalog_supplier'=> (bool) $latest['is_catalog_supplier'],
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function receivedPurchases(Product $product, Carbon $from, Carbon $to): Collection
    {
        if (! Schema::hasTable('inventories_moves')) {
            return collect();
        }

        return Move::query()
            ->with(['purchaseOrderLine.order.partner'])
            ->where('product_id', $product->id)
            ->where('state', MoveState::DONE)
            ->whereBetween('updated_at', [$from, $to])
            ->whereNotNull('purchase_order_line_id')
            ->get()
            ->groupBy(fn (Move $move): ?int => $move->purchaseOrderLine?->order?->partner_id)
            ->map(function (Collection $moves): array {
                /** @var Move $latestMove */
                $latestMove = $moves->sortByDesc('updated_at')->first();
                $partner = $latestMove->purchaseOrderLine?->order?->partner;

                return [
                    'supplier_id'        => $partner?->id,
                    'supplier_name'      => $partner?->name ?? '—',
                    'last_purchase_date' => $latestMove->updated_at,
                    'last_price'         => (float) ($latestMove->price_unit ?? $latestMove->purchaseOrderLine?->price_unit ?? 0),
                    'total_qty'          => (float) $moves->sum('product_qty'),
                    'currency_id'        => $latestMove->purchaseOrderLine?->currency_id,
                    'is_catalog_supplier'=> false,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function catalogSuppliers(Product $product): Collection
    {
        if (! Schema::hasTable('products_product_suppliers')) {
            return collect();
        }

        return ProductSupplier::query()
            ->with('partner')
            ->where('product_id', $product->id)
            ->get()
            ->map(fn (ProductSupplier $supplier): array => [
                'supplier_id'        => $supplier->partner_id,
                'supplier_name'      => $supplier->partner?->name ?? '—',
                'last_purchase_date' => null,
                'last_price'         => (float) ($supplier->price_discounted ?? $supplier->price ?? 0),
                'total_qty'          => 0.0,
                'currency_id'        => $supplier->currency_id,
                'is_catalog_supplier'=> true,
            ]);
    }

    public static function formatOmr(?float $amount): string
    {
        $prefix = app()->getLocale() === 'ar' ? 'ر.ع. ' : 'OMR ';

        return $prefix.number_format((float) ($amount ?? 0), 3);
    }

    public function orderLineHistory(Product $product, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        if (! Schema::hasTable('purchases_order_lines')) {
            return collect();
        }

        $from ??= now()->subYear()->startOfDay();
        $to ??= now()->endOfDay();

        return OrderLine::query()
            ->with(['order.partner'])
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query->whereBetween('ordered_at', [$from, $to]))
            ->orderByDesc('created_at')
            ->get();
    }
}
