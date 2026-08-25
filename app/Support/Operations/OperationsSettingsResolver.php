<?php

namespace App\Support\Operations;

use App\Enums\Operations\BackupFrequency;
use App\Enums\Operations\BackupType;
use App\Settings\OperationsSettings;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

class OperationsSettingsResolver
{
    public static function resolve(): OperationsSettings
    {
        try {
            $settings = app(OperationsSettings::class);
            $settings->toArray();

            return $settings;
        } catch (MissingSettings) {
            return static::defaults();
        }
    }

    public static function defaults(): OperationsSettings
    {
        return OperationsSettings::fake([
            'monitoring_enabled'            => true,
            'alerts_enabled'                => true,
            'disk_warning_threshold'        => 70,
            'disk_critical_threshold'       => 85,
            'failed_jobs_threshold'         => 5,
            'scheduler_warning_minutes'     => 5,
            'scheduler_critical_minutes'    => 15,
            'health_history_retention_days' => 30,
            'backup_enabled'                => false,
            'backup_frequency'              => BackupFrequency::Disabled,
            'backup_time'                   => '02:00',
            'backup_type'                   => BackupType::Database,
            'backup_disk'                   => 'private',
            'backup_retention_count'        => 7,
            'backup_alert_on_failure'       => true,
        ], loadMissingValues: false);
    }
}
