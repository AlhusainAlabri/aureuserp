<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_task_reminder_preferences')) {
            return;
        }

        Schema::create('user_task_reminder_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('notify_same_day')->default(true);
            $table->boolean('notify_one_day_before')->default(true);
            $table->boolean('notify_three_days_before')->default(true);
            $table->boolean('notify_seven_days_before')->default(false);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('database_enabled')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_task_reminder_preferences');
    }
};
