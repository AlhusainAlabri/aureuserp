<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * currencies.rounding was decimal(8,2), so the 0.001 rounding of three-decimal
 * currencies (OMR, KWD, BHD, JOD, TND…) was stored as 0.00 and every invoice
 * calculation in those currencies failed with "precision_rounding must be positive".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        Schema::table('currencies', function (Blueprint $table): void {
            $table->decimal('rounding', 12, 6)->default(0.01)->change();
        });

        DB::table('currencies')
            ->where('rounding', '<=', 0)
            ->get(['id', 'decimal_places'])
            ->each(fn (object $currency) => DB::table('currencies')
                ->where('id', $currency->id)
                ->update(['rounding' => 10 ** -((int) $currency->decimal_places)]));
    }

    public function down(): void
    {
        // The wider column is harmless to keep.
    }
};
