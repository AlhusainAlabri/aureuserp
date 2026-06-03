<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_borrowings', function (Blueprint $table): void {
            $table->string('borrow_signature_path')->nullable()->after('rejection_reason');
            $table->string('return_signature_path')->nullable()->after('borrow_signature_path');
            $table->timestamp('borrow_signed_at')->nullable()->after('return_signature_path');
            $table->timestamp('return_signed_at')->nullable()->after('borrow_signed_at');
            $table->foreignId('borrow_signed_by')->nullable()->after('return_signed_at')->constrained('users')->nullOnDelete();
            $table->foreignId('return_signed_by')->nullable()->after('borrow_signed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('due_reminder_sent_at')->nullable()->after('return_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('asset_borrowings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('borrow_signed_by');
            $table->dropConstrainedForeignId('return_signed_by');
            $table->dropColumn([
                'borrow_signature_path',
                'return_signature_path',
                'borrow_signed_at',
                'return_signed_at',
                'due_reminder_sent_at',
            ]);
        });
    }
};
