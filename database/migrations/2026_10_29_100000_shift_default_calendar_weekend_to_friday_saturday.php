<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The seeded default calendar worked Monday to Friday. The Omani working week is Sunday to
 * Thursday, so move the untouched seeded Friday rows to Sunday. Calendars that already have
 * Sunday rows (or no seeded Friday rows) are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('calendar_attendances') || ! Schema::hasTable('calendars')) {
            return;
        }

        $calendarIds = DB::table('calendars')
            ->whereIn('name', ['Standard 40 hours/week', 'قياسي 40 ساعة أسبوعيًا'])
            ->pluck('id');

        foreach ($calendarIds as $calendarId) {
            $rows = DB::table('calendar_attendances')->where('calendar_id', $calendarId);

            if ((clone $rows)->where('day_of_week', 'sunday')->exists()) {
                continue;
            }

            DB::table('calendar_attendances')
                ->where('calendar_id', $calendarId)
                ->where('day_of_week', 'friday')
                ->whereIn('name', ['Friday Morning', 'Friday Lunch', 'Friday Afternoon'])
                ->update([
                    'day_of_week' => 'sunday',
                    'name'        => DB::raw("REPLACE(name, 'Friday', 'Sunday')"),
                    'updated_at'  => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left as is: the previous weekend was a seed default, not user data.
    }
};
