<?php

namespace App\Services\Purchases;

use App\Enums\Purchases\RequestType;
use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Facades\PurchaseOrder as PurchaseOrderFacade;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Models\PurchaseOrder;

class PurchaseExpenseConversionService
{
    public function convertIfEligible(Order $order): void
    {
        if (! $order instanceof PurchaseOrder) {
            return;
        }

        if (! $order->isApproved()) {
            return;
        }

        if (! in_array($order->state, [OrderState::PURCHASE, OrderState::DONE], true)) {
            return;
        }

        if ($order->accountMoves()->exists()) {
            return;
        }

        $this->ensureBillPrerequisites($order);

        $order->refresh();

        if ($this->isInternalRequest($order) && $order->lines()->count() === 0) {
            app(InternalRequestLineService::class)->syncFromFormData($order, []);
            PurchaseOrderFacade::computePurchaseOrder($order->fresh());
            $order->refresh();
        }

        if ($order->qty_to_invoice <= 0 || empty($order->partner_id)) {
            return;
        }

        PurchaseOrderFacade::createPurchaseOrderBill($order);

        app(PurchasePaymentService::class)->syncAmounts($order->fresh());
    }

    protected function ensureBillPrerequisites(PurchaseOrder $order): void
    {
        if (empty($order->partner_id)) {
            $partnerId = PurchaseOrderResourceExtensions::defaultMiscSupplierId();

            if ($partnerId) {
                $order->update(['partner_id' => $partnerId]);
            }
        }

        if (! $this->isInternalRequest($order) || $order->qty_to_invoice > 0) {
            return;
        }

        foreach ($order->lines as $line) {
            $qtyToInvoice = (float) $line->product_qty - (float) $line->qty_invoiced;

            if ($qtyToInvoice <= 0) {
                continue;
            }

            $line->update(['qty_to_invoice' => $qtyToInvoice]);
        }
    }

    public function defaultMiscSupplierId(): ?int
    {
        return PurchaseOrderResourceExtensions::defaultMiscSupplierId();
    }

    public function isInternalRequest(Order $order): bool
    {
        $requestType = $order->request_type;

        if ($requestType instanceof RequestType) {
            return $requestType !== RequestType::StandardPurchase;
        }

        if (is_string($requestType) && $requestType !== '') {
            return $requestType !== RequestType::StandardPurchase->value;
        }

        return false;
    }
}
