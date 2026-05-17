<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases_orders', 'requesting_department_id')) {
                $table->unsignedBigInteger('requesting_department_id')->nullable()->after('company_id');
                $table->foreign('requesting_department_id')->references('id')->on('employees_departments')->onDelete('set null');
            }

            if (! Schema::hasColumn('purchases_orders', 'beneficiary_department_id')) {
                $table->unsignedBigInteger('beneficiary_department_id')->nullable()->after('requesting_department_id');
                $table->foreign('beneficiary_department_id')->references('id')->on('employees_departments')->onDelete('set null');
            }

            if (! Schema::hasColumn('purchases_orders', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('beneficiary_department_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'meeting_id')) {
                $table->unsignedBigInteger('meeting_id')->nullable()->after('project_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_uploaded')) {
                $table->boolean('receipt_uploaded')->default(false)->after('meeting_id');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('receipt_uploaded');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_uploaded_at')) {
                $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_path');
            }

            if (! Schema::hasColumn('purchases_orders', 'receipt_reminder_sent_at')) {
                $table->timestamp('receipt_reminder_sent_at')->nullable()->after('receipt_uploaded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases_orders', function (Blueprint $table) {
            $columns = [
                'requesting_department_id',
                'beneficiary_department_id',
                'project_id',
                'meeting_id',
                'receipt_uploaded',
                'receipt_path',
                'receipt_uploaded_at',
                'receipt_reminder_sent_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('purchases_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
