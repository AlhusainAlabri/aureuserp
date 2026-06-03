<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_salary_components', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->string('type');
            $table->string('calculation_type');
            $table->decimal('default_amount', 12, 3)->nullable();
            $table->decimal('default_percent', 5, 2)->nullable();
            $table->text('formula')->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            if (Schema::hasTable('accounts_accounts')) {
                $table->foreign('account_id')->references('id')->on('accounts_accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_salary_components');
    }
};
