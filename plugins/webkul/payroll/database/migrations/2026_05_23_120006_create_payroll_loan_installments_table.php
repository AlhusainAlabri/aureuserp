<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_loan_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_id')->constrained('payroll_loans')->cascadeOnDelete();
            $table->foreignId('payslip_id')->nullable()->constrained('payroll_payslips')->nullOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('amount', 12, 3);
            $table->string('status')->default('scheduled');
            $table->timestamp('deducted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_loan_installments');
    }
};
