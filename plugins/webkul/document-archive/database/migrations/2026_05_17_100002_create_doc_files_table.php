<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_files', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('folder_id')->constrained('doc_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('original_filename');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type');
            $table->string('extension');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('tag_color')->nullable();
            $table->string('password_hash')->nullable();
            $table->boolean('is_private')->default(false);
            $table->date('expiry_date')->nullable();
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->unsignedBigInteger('correspondence_id')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('meeting_id');
            $table->index('correspondence_id');
            $table->index(['folder_id', 'company_id']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_files');
    }
};
