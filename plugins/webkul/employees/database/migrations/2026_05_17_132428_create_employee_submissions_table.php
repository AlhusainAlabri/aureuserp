<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees_employee_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->enum('type', ['complaint', 'suggestion', 'inquiry', 'feedback'])->default('feedback');
            $table->string('subject');
            $table->text('body');
            $table->unsignedBigInteger('employee_id');
            $table->string('submitter_name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->enum('status', ['open', 'under_review', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->json('attachments')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees_employees')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('employees_departments')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees_employee_submissions');
    }
};
