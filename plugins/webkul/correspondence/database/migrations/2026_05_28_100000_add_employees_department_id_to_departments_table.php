<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table): void {
            if (! Schema::hasColumn('departments', 'employees_department_id')) {
                if (Schema::hasTable('employees_departments')) {
                    $table->foreignId('employees_department_id')
                        ->nullable()
                        ->after('company_id')
                        ->constrained('employees_departments')
                        ->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('employees_department_id')
                        ->nullable()
                        ->after('company_id');
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table): void {
            if (Schema::hasColumn('departments', 'employees_department_id')) {
                $table->dropConstrainedForeignId('employees_department_id');
            }
        });
    }
};
