<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees_employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->enum('document_type', ['id_card', 'passport', 'residence_permit', 'contract', 'certificate', 'other']);
            $table->string('document_name');
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('creator_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees_employee_documents');
    }
};
