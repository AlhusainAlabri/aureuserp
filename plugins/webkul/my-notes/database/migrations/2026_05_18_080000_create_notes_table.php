<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->enum('type', ['text', 'checklist', 'reminder', 'voice'])->default('text');
            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->string('color')->default('default');
            $table->json('tags')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('reminder_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->boolean('reminder_email_sent')->default(false);
            $table->foreignId('meeting_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('correspondence_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('audio_path')->nullable();
            $table->integer('audio_duration_seconds')->nullable();
            $table->text('audio_transcription')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
