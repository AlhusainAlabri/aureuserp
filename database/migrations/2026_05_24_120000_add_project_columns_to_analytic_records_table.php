<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('analytic_records')) {
            return;
        }

        Schema::table('analytic_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('analytic_records', 'project_id') && Schema::hasTable('projects_projects')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects_projects')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('analytic_records', 'task_id') && Schema::hasTable('projects_tasks')) {
                $table->unsignedBigInteger('task_id')->nullable()->after('project_id');
                $table->foreign('task_id')
                    ->references('id')
                    ->on('projects_tasks')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('analytic_records')) {
            return;
        }

        Schema::table('analytic_records', function (Blueprint $table): void {
            if (Schema::hasColumn('analytic_records', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }

            if (Schema::hasColumn('analytic_records', 'task_id')) {
                $table->dropForeign(['task_id']);
                $table->dropColumn('task_id');
            }
        });
    }
};
