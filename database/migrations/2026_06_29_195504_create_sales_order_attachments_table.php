<?php

use App\Support\ExtensionSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ExtensionSchema::ensureSalesOrderAttachmentsTable();
    }

    public function down(): void
    {
        ExtensionSchema::dropSalesOrderAttachmentsTable();
    }
};
