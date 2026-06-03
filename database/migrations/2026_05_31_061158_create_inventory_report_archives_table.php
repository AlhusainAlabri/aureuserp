<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_report_archives', function (Blueprint $table): void {
            $table->id();
            $table->string('report_type')->default('movement');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('file_path');
            $table->string('file_format')->default('pdf');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->json('filters')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_report_archives');
    }
};
