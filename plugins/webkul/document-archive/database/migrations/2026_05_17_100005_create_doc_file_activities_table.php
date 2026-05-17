<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_file_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('file_id')->constrained('doc_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', [
                'uploaded',
                'viewed',
                'downloaded',
                'shared',
                'renamed',
                'moved',
                'deleted',
                'version_added',
            ]);
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['file_id', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_file_activities');
    }
};
