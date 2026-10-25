<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('attendance.grace_minutes', 10);
        $this->migrator->add('attendance.overtime_multiplier_normal', 1.25);
        $this->migrator->add('attendance.overtime_multiplier_rest', 1.5);
        $this->migrator->add('attendance.overtime_multiplier_holiday', 1.5);
        $this->migrator->add('attendance.minimum_overtime_minutes', 30);
        $this->migrator->add('attendance.allow_self_checkin', true);
        $this->migrator->add('attendance.allowed_ip_ranges', []);
        $this->migrator->add('attendance.geofence', null);
        $this->migrator->add('attendance.workday_close_time', '23:30');
    }

    public function down(): void
    {
        foreach ([
            'grace_minutes',
            'overtime_multiplier_normal',
            'overtime_multiplier_rest',
            'overtime_multiplier_holiday',
            'minimum_overtime_minutes',
            'allow_self_checkin',
            'allowed_ip_ranges',
            'geofence',
            'workday_close_time',
        ] as $property) {
            $this->migrator->delete('attendance.'.$property);
        }
    }
};
