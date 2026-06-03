<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_salary_raises') || ! Schema::hasTable('employees_employees')) {
            return;
        }

        Schema::create('employee_salary_raises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->date('effective_date');
            $table->decimal('old_amount', 12, 3);
            $table->decimal('new_amount', 12, 3);
            $table->decimal('raise_amount', 12, 3);
            $table->decimal('raise_percent', 6, 2);
            $table->enum('reason', [
                'annual_review',
                'performance',
                'promotion',
                'cost_of_living',
                'market_adjustment',
                'other',
            ]);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('creator_id')->constrained('users');
            $table->timestamps();
            $table->index(['employee_id', 'effective_date']);
        });

        if (Schema::hasTable('employee_contracts')) {
            Schema::table('employee_salary_raises', function (Blueprint $table): void {
                $table->foreign('contract_id')->references('id')->on('employee_contracts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_raises');
    }
};
