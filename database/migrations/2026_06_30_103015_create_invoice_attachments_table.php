<?php

use App\Support\ExtensionSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ExtensionSchema::ensureInvoiceAttachmentsTable();
    }

    public function down(): void
    {
        ExtensionSchema::dropInvoiceAttachmentsTable();
    }
};
