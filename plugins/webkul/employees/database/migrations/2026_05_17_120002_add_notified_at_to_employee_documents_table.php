<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees_employee_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('employees_employee_documents', 'notified_at')) {
                $table->date('notified_at')->nullable()->after('expiry_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees_employee_documents', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
