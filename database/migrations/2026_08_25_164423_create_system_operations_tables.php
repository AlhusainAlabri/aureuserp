<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('overall_status', 20);
            $table->json('checks');
            $table->json('summary')->nullable();
            $table->string('triggered_by', 20)->default('scheduled');
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index('checked_at');
            $table->index('overall_status');
        });

        Schema::create('system_backups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->string('disk', 50)->default('private');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('failure_reason')->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('completed_at');
        });

        Schema::create('system_operation_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 80);
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_operation_logs');
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('system_health_snapshots');
    }
};
