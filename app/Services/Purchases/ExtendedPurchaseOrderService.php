<?php

namespace App\Services\Purchases;

use App\Mail\VendorPurchaseOrderMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Webkul\Account\Models\Partner;
use Webkul\Purchase\Enums as PurchaseEnums;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\PurchaseOrder as CorePurchaseOrderService;

class ExtendedPurchaseOrderService extends CorePurchaseOrderService
{
    public function sendRFQ(Order $record, array $data): Order
    {
        $pdfPath = $this->generateRFQPdf($record);

        foreach ($data['vendors'] as $vendorId) {
            $vendor = Partner::find($vendorId);

            if ($vendor?->email) {
                Mail::to($vendor->email)->queue(new VendorPurchaseOrderMail(
                    $data['subject'],
                    $data['message'],
                    $pdfPath,
                ));
            }
        }

        $record->update([
            'state' => PurchaseEnums\OrderState::SENT,
        ]);

        $record = $this->computePurchaseOrder($record);

        $message = $record->addMessage([
            'body' => $data['message'],
            'type' => 'comment',
        ]);

        $record->addAttachments(
            [$this->chatterAttachmentPath($pdfPath)],
            ['message_id' => $message->id],
        );

        return $record;
    }

    public function sendPurchaseOrder(Order $record, array $data): Order
    {
        $pdfPath = $this->generatePurchaseOrderPdf($record);

        foreach ($data['vendors'] as $vendorId) {
            $vendor = Partner::find($vendorId);

            if ($vendor?->email) {
                Mail::to($vendor->email)->queue(new VendorPurchaseOrderMail(
                    $data['subject'],
                    $data['message'],
                    $pdfPath,
                ));
            }
        }

        $message = $record->addMessage([
            'body' => $data['message'],
            'type' => 'comment',
        ]);

        $record->addAttachments(
            [$this->chatterAttachmentPath($pdfPath)],
            ['message_id' => $message->id],
        );

        return $record;
    }

    public function generateRFQPdf($record): string
    {
        $filename = 'Request-for-Quotation-'.str_replace('/', '_', $record->name).'.pdf';
        $pdfPath = $this->pdfStoragePath('rfqs', $filename);

        if (! Storage::disk('private')->exists($pdfPath)) {
            $pdf = Pdf::loadView('purchases::filament.admin.clusters.orders.orders.actions.print-quotation', [
                'records' => [$record],
            ]);

            Storage::disk('private')->put($pdfPath, $pdf->output());
        }

        return $pdfPath;
    }

    public function generatePurchaseOrderPdf($record): string
    {
        $filename = 'Purchase-Order-'.str_replace('/', '_', $record->name).'.pdf';
        $pdfPath = $this->pdfStoragePath('orders', $filename);

        if (! Storage::disk('private')->exists($pdfPath)) {
            $pdf = Pdf::loadView('purchases::filament.admin.clusters.orders.orders.actions.print-purchase-order', [
                'records' => [$record],
            ]);

            Storage::disk('private')->put($pdfPath, $pdf->output());
        }

        return $pdfPath;
    }

    protected function pdfStoragePath(string $folder, string $filename): string
    {
        return "purchases/{$folder}/".now()->year.'/'.$filename;
    }

    /**
     * Chatter attachments still read from the public disk — mirror private PDFs temporarily.
     */
    protected function chatterAttachmentPath(string $privatePath): string
    {
        if (Storage::disk('public')->exists($privatePath)) {
            return $privatePath;
        }

        if (Storage::disk('private')->exists($privatePath)) {
            Storage::disk('public')->put($privatePath, Storage::disk('private')->get($privatePath));
        }

        return $privatePath;
    }
}
