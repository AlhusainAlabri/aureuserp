<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_folders', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->string('password_hash')->nullable();
            $table->boolean('is_private')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('doc_folders')
                ->nullOnDelete();

            $table->index(['company_id', 'parent_id']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_folders');
    }
};
