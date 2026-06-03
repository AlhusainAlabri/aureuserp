<?php

use App\Services\Hr\HrExtensionSchemaService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(HrExtensionSchemaService::class)->ensure();
    }

    public function down(): void
    {
        // Intentionally empty — original migrations handle rollback.
    }
};
