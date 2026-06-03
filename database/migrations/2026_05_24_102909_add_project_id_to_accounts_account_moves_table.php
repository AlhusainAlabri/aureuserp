<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

        if (Schema::hasTable('projects_projects') && Schema::hasColumn('accounts_account_moves', 'project_id')) {
            Schema::table('accounts_account_moves', function (Blueprint $table): void {
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects_projects')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts_account_moves') || ! Schema::hasColumn('accounts_account_moves', 'project_id')) {
            return;
        }

        Schema::table('accounts_account_moves', function (Blueprint $table): void {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
