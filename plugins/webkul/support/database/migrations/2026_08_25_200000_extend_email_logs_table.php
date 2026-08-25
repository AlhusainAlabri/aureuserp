<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('email_logs', 'email_template_id')) {
                $table->foreignId('email_template_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('email_templates')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('email_logs', 'variables')) {
                $table->json('variables')->nullable()->after('subject');
            }

            if (! Schema::hasColumn('email_logs', 'sender_user_id')) {
                $table->foreignId('sender_user_id')
                    ->nullable()
                    ->after('variables')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('email_logs', 'mail_class')) {
                $table->string('mail_class')->nullable()->after('sender_user_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('email_logs', 'mail_class')) {
                $table->dropColumn('mail_class');
            }

            if (Schema::hasColumn('email_logs', 'sender_user_id')) {
                $table->dropConstrainedForeignId('sender_user_id');
            }

            if (Schema::hasColumn('email_logs', 'variables')) {
                $table->dropColumn('variables');
            }

            if (Schema::hasColumn('email_logs', 'email_template_id')) {
                $table->dropConstrainedForeignId('email_template_id');
            }
        });
    }
};
