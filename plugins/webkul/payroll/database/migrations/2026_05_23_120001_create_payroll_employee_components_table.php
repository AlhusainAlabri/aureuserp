<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_employee_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('payroll_salary_components')->cascadeOnDelete();
            $table->decimal('amount', 12, 3)->nullable();
            $table->decimal('percent', 5, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_employee_components');
    }
};
