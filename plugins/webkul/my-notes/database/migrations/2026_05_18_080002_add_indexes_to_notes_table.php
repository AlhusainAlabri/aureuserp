<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->index(['user_id', 'is_archived', 'is_pinned'], 'notes_user_archive_pin_index');
            $table->index(['user_id', 'type'], 'notes_user_type_index');
            $table->index(['user_id', 'reminder_at'], 'notes_user_reminder_index');
            $table->index(['company_id', 'created_at'], 'notes_company_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->dropIndex('notes_user_archive_pin_index');
            $table->dropIndex('notes_user_type_index');
            $table->dropIndex('notes_user_reminder_index');
            $table->dropIndex('notes_company_created_index');
        });
    }
};
