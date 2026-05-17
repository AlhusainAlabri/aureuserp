<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_share_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('file_id')->constrained('doc_files')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->string('shared_with_email')->nullable();
            $table->boolean('view_once')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['file_id', 'is_active']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_share_links');
    }
};
