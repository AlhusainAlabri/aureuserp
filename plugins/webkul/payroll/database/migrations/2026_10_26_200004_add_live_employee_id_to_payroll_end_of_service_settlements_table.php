<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string TABLE = 'payroll_end_of_service_settlements';

    private const string INDEX = 'payroll_eos_live_employee_unique';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (! Schema::hasColumn(self::TABLE, 'live_employee_id')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unsignedBigInteger('live_employee_id')->nullable()->after('status');
            });
        }

        DB::table(self::TABLE)
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->whereNull('live_employee_id')
            ->update(['live_employee_id' => DB::raw('employee_id')]);

        if (! Schema::hasIndex(self::TABLE, self::INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique('live_employee_id', self::INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'live_employee_id')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            if (Schema::hasIndex(self::TABLE, self::INDEX)) {
                $table->dropUnique(self::INDEX);
            }

            $table->dropColumn('live_employee_id');
        });
    }
};
