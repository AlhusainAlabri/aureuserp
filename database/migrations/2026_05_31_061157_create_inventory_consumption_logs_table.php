<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_consumption_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('move_id')->nullable();
            $table->unsignedBigInteger('operation_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 16, 4);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->text('purpose');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            if (Schema::hasTable('inventories_moves')) {
                $table->foreign('move_id')
                    ->references('id')
                    ->on('inventories_moves')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('inventories_operations')) {
                $table->foreign('operation_id')
                    ->references('id')
                    ->on('inventories_operations')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('products_products')) {
                $table->foreign('product_id')
                    ->references('id')
                    ->on('products_products')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_consumption_logs');
    }
};
