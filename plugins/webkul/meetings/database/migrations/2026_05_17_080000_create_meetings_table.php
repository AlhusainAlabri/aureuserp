<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('meeting_number')->unique();
            $table->enum('type', ['internal', 'external', 'emergency', 'board']);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'confirmed', 'archived'])->default('draft');
            $table->string('location')->nullable();
            $table->dateTime('meeting_date');
            $table->integer('duration_minutes')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects_projects')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('chair_person_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('secretary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('agenda')->nullable();
            $table->longText('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
