<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_folder_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id')->constrained('doc_folders')->cascadeOnDelete();
            $table->enum('type', ['user', 'role']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_name')->nullable();
            $table->enum('permission', ['view', 'upload', 'manage']);
            $table->timestamps();

            $table->index(['folder_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_folder_permissions');
    }
};
