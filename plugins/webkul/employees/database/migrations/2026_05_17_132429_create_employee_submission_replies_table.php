<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees_employee_submission_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->unsignedBigInteger('replied_by');
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('employees_employee_submissions')->onDelete('cascade');
            $table->foreign('replied_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees_employee_submission_replies');
    }
};
