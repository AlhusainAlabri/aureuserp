<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_overtime_requests')) {
            return;
        }

        Schema::create('attendance_overtime_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('minutes')->default(0);
            $table->string('day_type', 20)->default('normal');
            $table->decimal('multiplier', 5, 2)->default(1);
            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date'], 'attendance_overtime_employee_date_unique');
            $table->index(['status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_overtime_requests');
    }
};
