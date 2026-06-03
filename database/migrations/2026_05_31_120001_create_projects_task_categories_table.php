<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects_task_categories')) {
            return;
        }

        Schema::create('projects_task_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('color', 20)->default('#6366f1');
            $table->boolean('is_active')->default(true);
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('projects_tasks') && Schema::hasColumn('projects_tasks', 'category_id')) {
            Schema::table('projects_tasks', function (Blueprint $table): void {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('projects_task_categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects_tasks') && Schema::hasColumn('projects_tasks', 'category_id')) {
            Schema::table('projects_tasks', function (Blueprint $table): void {
                $table->dropForeign(['category_id']);
            });
        }

        Schema::dropIfExists('projects_task_categories');
    }
};
