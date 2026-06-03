<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects_tasks')) {
            return;
        }

        Schema::table('projects_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects_tasks', 'correspondence_id')) {
                $table->foreignId('correspondence_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('correspondences')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects_tasks')) {
            return;
        }

        Schema::table('projects_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('projects_tasks', 'correspondence_id')) {
                $table->dropConstrainedForeignId('correspondence_id');
            }
        });
    }
};
