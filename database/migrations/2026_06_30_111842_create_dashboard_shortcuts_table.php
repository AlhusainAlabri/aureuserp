<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_shortcuts', function (Blueprint $table): void {
            $table->id();
            $table->string('title_en');
            $table->string('title_ar')->nullable();
            $table->string('url');
            $table->string('icon')->default('heroicon-o-link');
            $table->string('color')->default('gray');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('opens_in_new_tab')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_shortcuts');
    }
};
