<?php

use App\Support\ExtensionSchema;
use Illuminate\Support\Facades\Schema;

it('skips sales order attachments when the sales orders table is missing', function (): void {
    Schema::shouldReceive('hasTable')
        ->with('sales_order_attachments')
        ->once()
        ->andReturn(false);

    Schema::shouldReceive('hasTable')
        ->with('sales_orders')
        ->once()
        ->andReturn(false);

    Schema::shouldReceive('create')->never();

    ExtensionSchema::ensureSalesOrderAttachmentsTable();
});

it('skips purchase order attachments when the purchases orders table is missing', function (): void {
    Schema::shouldReceive('hasTable')
        ->with('purchase_order_attachments')
        ->once()
        ->andReturn(false);

    Schema::shouldReceive('hasTable')
        ->with('purchases_orders')
        ->once()
        ->andReturn(false);

    Schema::shouldReceive('create')->never();

    ExtensionSchema::ensurePurchaseOrderAttachmentsTable();
});

it('skips invoice attachments when the account moves table is missing', function (): void {
    Schema::shouldReceive('hasTable')
        ->with('invoice_attachments')
        ->once()
        ->andReturn(false);

    Schema::shouldReceive('hasTable')
        ->with('accounts_account_moves')
        ->once()
        ->andReturn(false);

    Schema::shouldReceive('create')->never();

    ExtensionSchema::ensureInvoiceAttachmentsTable();
});

it('runs attachment table checks only once per request', function (): void {
    $reflection = new ReflectionClass(ExtensionSchema::class);
    $property = $reflection->getProperty('attachmentTablesChecked');
    $property->setAccessible(true);
    $property->setValue(null, false);

    Schema::spy();

    ExtensionSchema::ensureAttachmentTables();
    ExtensionSchema::ensureAttachmentTables();

    Schema::shouldHaveReceived('hasTable')->times(6);
});
