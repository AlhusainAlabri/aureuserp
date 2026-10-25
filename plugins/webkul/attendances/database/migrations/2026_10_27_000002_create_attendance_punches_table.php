<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_punches')) {
            return;
        }

        Schema::create('attendance_punches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->dateTime('punched_at');
            $table->string('source', 20);
            $table->string('direction', 10)->nullable();
            $table->string('device_ref')->nullable();
            $table->foreignId('import_batch_id')->nullable()->constrained('attendance_import_batches')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'punched_at', 'source'], 'attendance_punches_employee_instant_source_unique');
            $table->index('punched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_punches');
    }
};
