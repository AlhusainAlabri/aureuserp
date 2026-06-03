<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_employees', function (Blueprint $table): void {
            $table->foreignId('employment_type_id')
                ->nullable()
                ->after('job_id')
                ->constrained('employees_employment_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees_employees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('employment_type_id');
        });
    }
};
