<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('analytic_records')) {
            return;
        }

        Schema::table('analytic_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('analytic_records', 'project_id') && Schema::hasTable('projects_projects')) {
                $table->foreignId('project_id')
                    ->nullable()
                    ->constrained('projects_projects')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('analytic_records', 'task_id') && Schema::hasTable('projects_tasks')) {
                $table->foreignId('task_id')
                    ->nullable()
                    ->constrained('projects_tasks')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('analytic_records')) {
            return;
        }

        Schema::table('analytic_records', function (Blueprint $table): void {
            if (Schema::hasColumn('analytic_records', 'project_id')) {
                $table->dropConstrainedForeignId('project_id');
            }

            if (Schema::hasColumn('analytic_records', 'task_id')) {
                $table->dropConstrainedForeignId('task_id');
            }
        });
    }
};
