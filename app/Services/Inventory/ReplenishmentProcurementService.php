<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\DefaultProcurement;
use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use App\Models\Inventory\InventoryReplenishmentPreference;
use App\Services\Purchases\InternalRequestLineService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductSupplier;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Facades\PurchaseOrder as PurchaseOrderFacade;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Purchase\Models\OrderLine;
use Webkul\Purchase\Models\PurchaseOrder;

class ReplenishmentProcurementService
{
    public function __construct(
        private readonly InternalRequestLineService $internalRequestLineService,
    ) {}

    public function preferenceFor(OrderPoint $orderPoint): InventoryReplenishmentPreference
    {
        return InventoryReplenishmentPreference::query()->firstOrCreate(
            ['order_point_id' => $orderPoint->id],
            [
                'default_procurement'  => DefaultProcurement::InternalRequest,
                'default_request_type' => RequestType::OfficeSupplies,
            ],
        );
    }

    public function createInternalRequest(OrderPoint $orderPoint, ?RequestType $requestType = null): PurchaseOrder
    {
        $preference = $this->preferenceFor($orderPoint);
        $requestType ??= $preference->default_request_type ?? RequestType::OfficeSupplies;
        $quantity = ReplenishmentResource::qtyToOrder($orderPoint);
        $product = Product::query()->findOrFail($orderPoint->product_id);
        $priceUnit = $this->resolveUnitPrice($product);

        return DB::transaction(function () use ($orderPoint, $requestType, $quantity, $product, $priceUnit): PurchaseOrder {
            $order = PurchaseOrder::query()->create($this->mergePurchaseOrderAttributes([
                'name'                     => $this->nextOrderName(),
                'state'                    => OrderState::DRAFT,
                'ordered_at'               => now(),
                'partner_id'               => PurchaseOrderResourceExtensions::defaultMiscSupplierId(),
                'currency_id'              => PurchaseOrderResourceExtensions::defaultOmrCurrencyId() ?? $product->currency_id,
                'company_id'               => $orderPoint->company_id ?? Auth::user()?->default_company_id,
                'creator_id'               => Auth::id(),
                'request_type'             => $requestType->value,
                'requesting_department_id' => PurchaseOrderResourceExtensions::defaultRequestingDepartmentId(),
                'origin'                   => __('inventory-extensions::procurement.internal_origin', [
                    'product' => $product->name,
                ]),
            ]));

            $this->internalRequestLineService->syncFromFormData($order, [[
                'name'        => $product->name,
                'product_qty' => $quantity,
                'price_unit'  => $priceUnit,
                'product_id'  => PurchaseOrderResourceExtensions::defaultInternalLineProductId() ?? $product->id,
                'uom_id'      => $product->uom_id,
            ]]);

            PurchaseOrderFacade::computePurchaseOrder($order->fresh());

            return $order->fresh(['lines']);
        });
    }

    public function createDraftPurchaseOrder(OrderPoint $orderPoint): PurchaseOrder
    {
        $quantity = ReplenishmentResource::qtyToOrder($orderPoint);
        $product = Product::query()->findOrFail($orderPoint->product_id);
        $supplier = $this->resolveBestSupplier($product);
        $priceUnit = $supplier?->price ?? $this->resolveUnitPrice($product);

        return DB::transaction(function () use ($orderPoint, $quantity, $product, $supplier, $priceUnit): PurchaseOrder {
            $order = PurchaseOrder::query()->create($this->mergePurchaseOrderAttributes([
                'name'        => $this->nextOrderName(),
                'state'       => OrderState::DRAFT,
                'ordered_at'  => now(),
                'partner_id'  => $this->resolvePartnerIdForDraftPo($product),
                'currency_id' => $supplier?->currency_id ?? PurchaseOrderResourceExtensions::defaultOmrCurrencyId(),
                'company_id'  => $orderPoint->company_id ?? Auth::user()?->default_company_id,
                'creator_id'  => Auth::id(),
                'request_type'=> RequestType::StandardPurchase->value,
                'origin'      => __('inventory-extensions::procurement.po_origin', [
                    'product' => $product->name,
                ]),
            ]));

            OrderLine::query()->create([
                'order_id'       => $order->id,
                'product_id'     => $product->id,
                'uom_id'         => $product->uom_id,
                'name'           => $product->name,
                'product_qty'    => $quantity,
                'product_uom_qty'=> $quantity,
                'price_unit'     => $priceUnit,
                'price_subtotal' => $quantity * $priceUnit,
                'price_total'    => $quantity * $priceUnit,
                'company_id'     => $order->company_id,
                'currency_id'    => $order->currency_id,
                'creator_id'     => Auth::id(),
            ]);

            PurchaseOrderFacade::computePurchaseOrder($order->fresh());

            return $order->fresh(['lines']);
        });
    }

    public function internalRequestUrl(OrderPoint $orderPoint, ?RequestType $requestType = null): string
    {
        $requestType ??= $this->preferenceFor($orderPoint)->default_request_type ?? RequestType::OfficeSupplies;

        return PurchaseOrderResource::getUrl('create', [
            'request_type' => $requestType->value,
            'product_id'   => $orderPoint->product_id,
            'quantity'     => ReplenishmentResource::qtyToOrder($orderPoint),
        ]);
    }

    public function editOrderUrl(PurchaseOrder $order): string
    {
        return PurchaseOrderResource::getUrl('edit', ['record' => $order]);
    }

    public function hasOpenProcurement(OrderPoint $orderPoint): bool
    {
        if (! Schema::hasTable('purchases_order_lines')) {
            return false;
        }

        return OrderLine::query()
            ->where('product_id', $orderPoint->product_id)
            ->whereHas('order', fn ($query) => $query->whereIn('state', [
                OrderState::DRAFT->value,
                OrderState::SENT->value,
                OrderState::PURCHASE->value,
            ]))
            ->exists();
    }

    public function processAutomaticReplenishment(OrderPoint $orderPoint): ?PurchaseOrder
    {
        if (ReplenishmentResource::qtyToOrder($orderPoint) <= 0) {
            return null;
        }

        if ($this->hasOpenProcurement($orderPoint)) {
            return null;
        }

        $preference = $this->preferenceFor($orderPoint);

        if (! $this->canProcessAutomaticReplenishment($orderPoint, $preference)) {
            return null;
        }

        return match ($preference->default_procurement) {
            DefaultProcurement::DraftPo         => $this->createDraftPurchaseOrder($orderPoint),
            DefaultProcurement::InternalRequest => $this->createInternalRequest($orderPoint),
        };
    }

    protected function canProcessAutomaticReplenishment(
        OrderPoint $orderPoint,
        InventoryReplenishmentPreference $preference,
    ): bool {
        $product = Product::query()->find($orderPoint->product_id);

        if ($product === null) {
            return false;
        }

        return match ($preference->default_procurement) {
            DefaultProcurement::DraftPo         => $this->resolvePartnerIdForDraftPo($product) !== null,
            DefaultProcurement::InternalRequest => PurchaseOrderResourceExtensions::defaultMiscSupplierId() !== null,
        };
    }

    protected function resolvePartnerIdForDraftPo(Product $product): ?int
    {
        $supplier = $this->resolveBestSupplier($product);

        return $supplier?->partner_id ?? PurchaseOrderResourceExtensions::defaultMiscSupplierId();
    }

    protected function resolveBestSupplier(Product $product): ?ProductSupplier
    {
        if (! Schema::hasTable('products_product_suppliers')) {
            return null;
        }

        return $product->sellers()
            ->orderBy('sort')
            ->orderByDesc('price')
            ->first();
    }

    protected function resolveUnitPrice(Product $product): float
    {
        $supplier = $this->resolveBestSupplier($product);

        if ($supplier !== null) {
            return (float) ($supplier->price_discounted ?? $supplier->price ?? 0);
        }

        return (float) ($product->cost ?? $product->price ?? 0);
    }

    protected function nextOrderName(): string
    {
        $year = now()->year;
        $latest = PurchaseOrder::query()
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('PO/%d-%04d', $year, $latest + 1);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function mergePurchaseOrderAttributes(array $attributes): array
    {
        if (! Schema::hasColumn('purchases_orders', 'request_type')) {
            unset($attributes['request_type']);
        }

        if (! Schema::hasColumn('purchases_orders', 'requesting_department_id')) {
            unset($attributes['requesting_department_id']);
        }

        return $attributes;
    }
}
