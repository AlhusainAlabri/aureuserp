<?php

use App\Support\ExtensionSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ExtensionSchema::ensurePurchaseOrderAttachmentsTable();
    }

    public function down(): void
    {
        ExtensionSchema::dropPurchaseOrderAttachmentsTable();
    }
};
