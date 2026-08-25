<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_templates')) {
            return;
        }

        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('subject');
            $table->longText('content');
            $table->text('description')->nullable();
            $table->string('layout')->nullable()->default('default');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('sender_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
