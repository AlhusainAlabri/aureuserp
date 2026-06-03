<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('department_employee')
            || ! Schema::hasTable('employees_employees')
            || ! Schema::hasTable('employees_departments')
        ) {
            return;
        }

        Schema::create('department_employee', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('employees_departments')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'department_id']);
            $table->index(['employee_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_employee');
    }
};
