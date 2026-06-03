<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_loans', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('employee_id')->constrained('employees_employees')->restrictOnDelete();
            $table->string('loan_type');
            $table->decimal('total_amount', 12, 3);
            $table->unsignedInteger('installment_count');
            $table->decimal('installment_amount', 12, 3);
            $table->unsignedSmallInteger('start_period_year');
            $table->unsignedTinyInteger('start_period_month');
            $table->unsignedSmallInteger('end_period_year');
            $table->unsignedTinyInteger('end_period_month');
            $table->text('reason');
            $table->string('status')->default('draft');
            $table->decimal('amount_repaid', 12, 3)->default(0);
            $table->decimal('amount_remaining', 12, 3);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_loans');
    }
};
