<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_trainings') || ! Schema::hasTable('employees_employees')) {
            return;
        }

        Schema::create('employee_trainings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->string('course_name');
            $table->string('provider')->nullable();
            $table->enum('type', ['internal', 'external', 'online', 'workshop', 'conference', 'certification'])->default('external');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('duration_hours', 6, 2)->nullable();
            $table->decimal('cost', 12, 3)->nullable();
            $table->string('cost_currency')->default('OMR');
            $table->string('certificate_path')->nullable();
            $table->date('certificate_expiry_date')->nullable();
            $table->date('certificate_notified_at')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->text('notes')->nullable();
            $table->foreignId('creator_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_trainings');
    }
};
