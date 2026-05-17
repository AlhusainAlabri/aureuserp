<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees_employees', 'membership_type')) {
                $table->enum('membership_type', ['employee', 'collaborator', 'volunteer'])->default('employee')->after('employee_type');
            }

            if (! Schema::hasColumn('employees_employees', 'civil_id')) {
                $table->string('civil_id')->nullable()->after('membership_type');
            }

            if (! Schema::hasColumn('employees_employees', 'civil_id_expiry')) {
                $table->date('civil_id_expiry')->nullable()->after('civil_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees_employees', function (Blueprint $table) {
            $table->dropColumn(['membership_type', 'civil_id', 'civil_id_expiry']);
        });
    }
};
