<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        if (! Schema::hasTable('projects_tasks')) {
            return;
        }

        Schema::table('projects_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('projects_tasks', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }

            foreach (['start_date', 'completed_at', 'priority_level', 'category_id', 'department_id'] as $column) {
                if (Schema::hasColumn('projects_tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
