<?php

namespace App\Console\Commands;

use App\Services\Hr\HrExtensionSchemaService;
use App\Support\ExtensionSchema;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('erp:ensure-extension-schema')]
#[Description('Create the columns and tables that app-level migrations add to plugin tables')]
class EnsureExtensionSchema extends Command
{
    public function handle(): int
    {
        $ran = ExtensionSchema::runDeferredMigrations();

        ExtensionSchema::ensureSalesOrderAttachmentsTable();
        ExtensionSchema::ensurePurchaseOrderAttachmentsTable();
        ExtensionSchema::ensureInvoiceAttachmentsTable();

        app(HrExtensionSchemaService::class)->ensure(force: true);

        // The HR tables created above are prerequisites (e.g. salary raise → contract foreign key).
        ExtensionSchema::runDeferredMigrations();

        $this->info(count($ran).' extension migrations checked, attachment and HR extension schema ensured.');

        return self::SUCCESS;
    }
}
