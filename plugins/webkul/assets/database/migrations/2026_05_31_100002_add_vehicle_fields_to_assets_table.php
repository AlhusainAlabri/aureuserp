<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->string('plate_number')->nullable()->after('location');
            $table->string('registration_number')->nullable()->after('plate_number');
            $table->unsignedInteger('mileage')->nullable()->after('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn(['plate_number', 'registration_number', 'mileage']);
        });
    }
};
