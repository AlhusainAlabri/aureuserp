<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every user save is mirrored into partners_partners, including is_active and
 * password, so these columns must exist whether or not the Website plugin
 * (which used to be the only place that created them) is installed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners_partners', function (Blueprint $table): void {
            if (! Schema::hasColumn('partners_partners', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (! Schema::hasColumn('partners_partners', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('partners_partners', 'password')) {
                $table->string('password')->nullable();
            }

            if (! Schema::hasColumn('partners_partners', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        // The Website plugin's own migration owns these columns when it is installed.
    }
};
