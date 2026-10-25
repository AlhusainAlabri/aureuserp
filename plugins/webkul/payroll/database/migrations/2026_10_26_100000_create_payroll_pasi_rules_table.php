<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Services\PasiDefaults;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_pasi_rules')) {
            Schema::create('payroll_pasi_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('category');
                $table->decimal('employee_rate', 5, 2)->default(0);
                $table->decimal('employer_rate', 5, 2)->default(0);
                $table->decimal('salary_cap', 12, 3)->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['category', 'effective_from']);
            });
        }

        PasiDefaults::seedRules();
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_pasi_rules');
    }
};
