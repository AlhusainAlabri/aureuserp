<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_file_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('file_id')->constrained('doc_files')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->string('original_filename');
            $table->string('change_note')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['file_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_file_versions');
    }
};
