<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventories_order_points')) {
            return;
        }

        Schema::create('inventory_replenishment_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_point_id')->unique();
            $table->enum('default_procurement', ['internal_request', 'draft_po'])->default('internal_request');
            $table->string('default_request_type')->nullable();
            $table->timestamps();

            $table->foreign('order_point_id')
                ->references('id')
                ->on('inventories_order_points')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_replenishment_preferences');
    }
};
