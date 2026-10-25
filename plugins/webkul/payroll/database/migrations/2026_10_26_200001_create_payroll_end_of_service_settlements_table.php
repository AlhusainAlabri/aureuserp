<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_end_of_service_settlements')) {
            return;
        }

        Schema::create('payroll_end_of_service_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->date('join_date');
            $table->date('departure_date');
            $table->unsignedInteger('service_days')->default(0);
            $table->decimal('last_basic', 12, 3)->default(0);
            $table->json('gratuity_breakdown')->nullable();
            $table->decimal('gratuity_amount', 12, 3)->default(0);
            $table->decimal('unused_leave_days', 8, 2)->default(0);
            $table->decimal('leave_payout_amount', 12, 3)->default(0);
            $table->decimal('loan_offset_amount', 12, 3)->default(0);
            $table->decimal('final_salary_amount', 12, 3)->default(0);
            $table->decimal('other_adjustment', 12, 3)->default(0);
            $table->text('adjustment_note')->nullable();
            $table->decimal('net_amount', 12, 3)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_end_of_service_settlements');
    }
};
