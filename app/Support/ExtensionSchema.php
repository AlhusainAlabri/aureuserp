<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtensionSchema
{
    protected static bool $attachmentTablesChecked = false;

    public static function ensureSalesOrderAttachmentsTable(): void
    {
        if (Schema::hasTable('sales_order_attachments') || ! Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::create('sales_order_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->default('application/octet-stream');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public static function ensurePurchaseOrderAttachmentsTable(): void
    {
        if (Schema::hasTable('purchase_order_attachments') || ! Schema::hasTable('purchases_orders')) {
            return;
        }

        Schema::create('purchase_order_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchases_orders')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->default('application/octet-stream');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public static function ensureInvoiceAttachmentsTable(): void
    {
        if (Schema::hasTable('invoice_attachments') || ! Schema::hasTable('accounts_account_moves')) {
            return;
        }

        Schema::create('invoice_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('accounts_account_moves')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->default('application/octet-stream');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public static function ensureAttachmentTables(): void
    {
        if (static::$attachmentTablesChecked) {
            return;
        }

        static::$attachmentTablesChecked = true;

        static::ensureSalesOrderAttachmentsTable();
        static::ensurePurchaseOrderAttachmentsTable();
        static::ensureInvoiceAttachmentsTable();
    }

    public static function dropSalesOrderAttachmentsTable(): void
    {
        Schema::dropIfExists('sales_order_attachments');
    }

    public static function dropPurchaseOrderAttachmentsTable(): void
    {
        Schema::dropIfExists('purchase_order_attachments');
    }

    public static function dropInvoiceAttachmentsTable(): void
    {
        Schema::dropIfExists('invoice_attachments');
    }
}
