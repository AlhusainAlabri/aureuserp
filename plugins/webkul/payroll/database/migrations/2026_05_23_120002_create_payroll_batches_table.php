<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->unique();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('pay_date');
            $table->string('status')->default('draft');
            $table->decimal('total_gross', 14, 3)->default(0);
            $table->decimal('total_deductions', 14, 3)->default(0);
            $table->decimal('total_net', 14, 3)->default(0);
            $table->decimal('total_employer_cost', 14, 3)->default(0);
            $table->unsignedInteger('employee_count')->default(0);
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('account_move_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('accounts_journals')) {
                $table->foreign('journal_id')->references('id')->on('accounts_journals')->nullOnDelete();
            }

            if (Schema::hasTable('accounts_account_moves')) {
                $table->foreign('account_move_id')->references('id')->on('accounts_account_moves')->nullOnDelete();
            }

            $table->unique(['company_id', 'period_year', 'period_month'], 'payroll_batches_company_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batches');
    }
};
