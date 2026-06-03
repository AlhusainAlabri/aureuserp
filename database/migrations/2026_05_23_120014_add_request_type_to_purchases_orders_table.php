<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchases_orders')) {
            return;
        }

        Schema::table('purchases_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchases_orders', 'request_type')) {
                $table->enum('request_type', [
                    'standard_purchase',
                    'device_request',
                    'technical_support',
                    'office_supplies',
                    'maintenance',
                    'other',
                ])->default('standard_purchase')->after('id');
            }

            if (! Schema::hasColumn('purchases_orders', 'urgency')) {
                $table->enum('urgency', ['low', 'normal', 'high', 'critical'])->default('normal');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchases_orders')) {
            return;
        }

        Schema::table('purchases_orders', function (Blueprint $table): void {
            foreach (['urgency', 'request_type'] as $column) {
                if (Schema::hasColumn('purchases_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
