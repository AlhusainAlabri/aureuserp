<?php

namespace App\Settings;

use App\Enums\Operations\BackupFrequency;
use App\Enums\Operations\BackupType;
use Spatie\LaravelSettings\Settings;

class OperationsSettings extends Settings
{
    public bool $monitoring_enabled;

    public bool $alerts_enabled;

    public int $disk_warning_threshold;

    public int $disk_critical_threshold;

    public int $failed_jobs_threshold;

    public int $scheduler_warning_minutes;

    public int $scheduler_critical_minutes;

    public int $health_history_retention_days;

    public bool $backup_enabled;

    public BackupFrequency $backup_frequency;

    public string $backup_time;

    public BackupType $backup_type;

    public string $backup_disk;

    public int $backup_retention_count;

    public bool $backup_alert_on_failure;

    public static function group(): string
    {
        return 'operations';
    }
}
