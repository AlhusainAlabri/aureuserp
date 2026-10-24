<?php

use App\Support\Invoices\InvoicePdf;
use App\Support\Pdf\Documents\AccountMoveDocument;
use App\Support\Pdf\Documents\PurchaseOrderDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Webkul\Account\Models\Move;
use Webkul\Purchase\Models\Order;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('builds an invoice document with columns, rows and totals', function (): void {
    $move = Move::factory()->create();

    $document = AccountMoveDocument::make($move->fresh(), 'invoice');

    expect($document)->toHaveKeys(['company', 'title', 'partyLabel', 'party', 'meta', 'columns', 'rows', 'totals', 'notes'])
        ->and($document['title'])->toContain((string) $move->fresh()->name)
        ->and(collect($document['totals'])->pluck(0))->not->toBeEmpty();
});

it('renders invoices and purchase orders as real PDFs in Arabic and English', function (string $locale): void {
    app()->setLocale($locale);

    $move = Move::factory()->create();
    $order = Order::factory()->create();

    $invoice = Pdf::loadView('pdf.trade-document', ['docs' => [AccountMoveDocument::make($move->fresh(), 'invoice')]])->output();
    $purchase = Pdf::loadView('purchases::filament.admin.clusters.orders.orders.actions.print-purchase-order', ['records' => [$order]])->output();

    expect($invoice)->toStartWith('%PDF')
        ->and($purchase)->toStartWith('%PDF')
        ->and(InvoicePdf::generate($move->fresh()))->toStartWith('%PDF');
})->with(['ar', 'en']);

it('titles a purchase order and a request for quotation differently', function (): void {
    $order = Order::factory()->create();

    app()->setLocale('en');

    expect(PurchaseOrderDocument::make($order, true)['title'])->toContain('Request for Quotation')
        ->and(PurchaseOrderDocument::make($order, false)['title'])->toContain('Purchase Order');
});
