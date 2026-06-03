<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payslips', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('batch_id')->constrained('payroll_batches')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees_employees')->restrictOnDelete();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('working_days', 4, 1)->default(22);
            $table->decimal('worked_days', 4, 1)->default(22);
            $table->decimal('unpaid_leave_days', 4, 1)->default(0);
            $table->decimal('basic_salary', 12, 3)->default(0);
            $table->decimal('gross_amount', 12, 3)->default(0);
            $table->decimal('deductions_amount', 12, 3)->default(0);
            $table->decimal('net_amount', 12, 3)->default(0);
            $table->decimal('employer_cost', 12, 3)->default(0);
            $table->string('payment_method')->default('bank_transfer');
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payslips');
    }
};
