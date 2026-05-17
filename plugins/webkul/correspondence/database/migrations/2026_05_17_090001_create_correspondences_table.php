<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondences', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->unique();
            $table->enum('direction', ['outgoing', 'incoming']);
            $table->enum('type', ['official', 'internal', 'external']);
            $table->enum('priority', ['normal', 'urgent', 'confidential'])->default('normal');
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->string('sender_name');
            $table->string('sender_entity')->nullable();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_external_email')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'sent', 'received', 'archived'])->default('draft');
            $table->date('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects_projects')->nullOnDelete();
            $table->foreignId('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->unsignedBigInteger('purchase_request_id')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('correspondences')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondences');
    }
};
