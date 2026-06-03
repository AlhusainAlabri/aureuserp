<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payslip_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payslip_id')->constrained('payroll_payslips')->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained('payroll_salary_components')->nullOnDelete();
            $table->string('type');
            $table->string('code');
            $table->string('name');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('rate', 12, 3)->nullable();
            $table->decimal('amount', 12, 3);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payslip_lines');
    }
};
