<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Services\GratuityDefaults;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_gratuity_tiers')) {
            Schema::create('payroll_gratuity_tiers', function (Blueprint $table): void {
                $table->id();
                $table->decimal('from_year', 5, 2);
                $table->decimal('to_year', 5, 2)->nullable();
                $table->decimal('days_per_year', 5, 2);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['effective_from', 'from_year']);
            });
        }

        GratuityDefaults::seedTiers();
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_gratuity_tiers');
    }
};
