<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payroll_salary_components', 'liability_account_id')) {
            return;
        }

        Schema::table('payroll_salary_components', function (Blueprint $table): void {
            $table->unsignedBigInteger('liability_account_id')->nullable()->after('account_id');

            if (Schema::hasTable('accounts_accounts')) {
                $table->foreign('liability_account_id')->references('id')->on('accounts_accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payroll_salary_components', 'liability_account_id')) {
            return;
        }

        Schema::table('payroll_salary_components', function (Blueprint $table): void {
            if (Schema::hasTable('accounts_accounts')) {
                $table->dropForeign(['liability_account_id']);
            }

            $table->dropColumn('liability_account_id');
        });
    }
};
