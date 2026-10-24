<?php

namespace App\Support;

use Illuminate\Database\QueryException;
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

    /**
     * Re-run the app-level migrations that extend plugin tables. On a clean
     * install they ran before the plugins existed, skipped themselves and were
     * recorded as ran, so the columns and tables they add are still missing.
     *
     * @return list<string> Names of the migrations that were run.
     */
    public static function runDeferredMigrations(): array
    {
        $ran = [];

        foreach (config('extension-schema.deferred_migrations', []) as $name) {
            $path = database_path("migrations/{$name}.php");

            if (! is_file($path)) {
                continue;
            }

            try {
                (require $path)->up();
            } catch (QueryException $exception) {
                if (! static::isAlreadyAppliedError($exception)) {
                    throw $exception;
                }
            }

            $ran[] = $name;
        }

        return $ran;
    }

    protected static function isAlreadyAppliedError(QueryException $exception): bool
    {
        // 1050 table exists, 1060 duplicate column, 1061 duplicate key name, 1826 duplicate foreign key.
        return in_array((int) ($exception->errorInfo[1] ?? 0), [1050, 1060, 1061, 1826], true);
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
