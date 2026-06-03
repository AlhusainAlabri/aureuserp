<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table): void {
            if (! Schema::hasColumn('notes', 'board_status')) {
                $table->string('board_status', 32)->default('inbox')->after('is_archived');
            }

            if (! Schema::hasColumn('notes', 'board_sort')) {
                $table->unsignedSmallInteger('board_sort')->default(0)->after('board_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table): void {
            if (Schema::hasColumn('notes', 'board_sort')) {
                $table->dropColumn('board_sort');
            }

            if (Schema::hasColumn('notes', 'board_status')) {
                $table->dropColumn('board_status');
            }
        });
    }
};
