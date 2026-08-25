<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureProjectsTasksColumns();
        $this->ensureAccountMovesProjectColumn();
        $this->ensureTaskCategoryForeignKey();
    }

    public function down(): void
    {
        // Intentionally empty — this migration repairs missed columns from earlier runs.
    }

    protected function ensureProjectsTasksColumns(): void
    {
        if (! Schema::hasTable('projects_tasks')) {
            return;
        }

        Schema::table('projects_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects_tasks', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('deadline');
            }

            if (! Schema::hasColumn('projects_tasks', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->after('start_date');
            }

            if (! Schema::hasColumn('projects_tasks', 'priority_level')) {
                $table->string('priority_level', 20)->default('medium')->index()->after('priority');
            }

            if (! Schema::hasColumn('projects_tasks', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('creator_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('projects_tasks', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('stage_id');
            }

            if (! Schema::hasColumn('projects_tasks', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('category_id');
            }
        });

        if (Schema::hasColumn('projects_tasks', 'priority_level')) {
            DB::table('projects_tasks')
                ->where('priority', true)
                ->update(['priority_level' => 'high']);

            DB::table('projects_tasks')
                ->where('priority', false)
                ->where('priority_level', 'medium')
                ->update(['priority_level' => 'medium']);
        }

        if (Schema::hasColumn('projects_tasks', 'completed_at')) {
            DB::table('projects_tasks')
                ->where('state', 'done')
                ->whereNull('completed_at')
                ->update(['completed_at' => DB::raw('updated_at')]);
        }
    }

    protected function ensureAccountMovesProjectColumn(): void
    {
        if (! Schema::hasTable('accounts_account_moves')) {
            return;
        }

        Schema::table('accounts_account_moves', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounts_account_moves', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('creator_id');
                $table->index('project_id');
            }
        });

        if (
            Schema::hasTable('projects_projects')
            && Schema::hasColumn('accounts_account_moves', 'project_id')
        ) {
            $foreignKeys = collect(Schema::getConnection()->getSchemaBuilder()->getForeignKeys('accounts_account_moves'))
                ->pluck('name')
                ->all();

            if (! in_array('accounts_account_moves_project_id_foreign', $foreignKeys, true)) {
                Schema::table('accounts_account_moves', function (Blueprint $table): void {
                    $table->foreign('project_id')
                        ->references('id')
                        ->on('projects_projects')
                        ->nullOnDelete();
                });
            }
        }
    }

    protected function ensureTaskCategoryForeignKey(): void
    {
        if (
            ! Schema::hasTable('projects_tasks')
            || ! Schema::hasTable('projects_task_categories')
            || ! Schema::hasColumn('projects_tasks', 'category_id')
        ) {
            return;
        }

        $foreignKeys = collect(Schema::getConnection()->getSchemaBuilder()->getForeignKeys('projects_tasks'))
            ->pluck('name')
            ->all();

        if (in_array('projects_tasks_category_id_foreign', $foreignKeys, true)) {
            return;
        }

        Schema::table('projects_tasks', function (Blueprint $table): void {
            $table->foreign('category_id')
                ->references('id')
                ->on('projects_task_categories')
                ->nullOnDelete();
        });
    }
};
