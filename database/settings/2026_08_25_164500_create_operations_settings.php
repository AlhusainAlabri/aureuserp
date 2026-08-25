<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('operations.monitoring_enabled', true);
        $this->migrator->add('operations.alerts_enabled', true);
        $this->migrator->add('operations.disk_warning_threshold', 70);
        $this->migrator->add('operations.disk_critical_threshold', 85);
        $this->migrator->add('operations.failed_jobs_threshold', 5);
        $this->migrator->add('operations.scheduler_warning_minutes', 5);
        $this->migrator->add('operations.scheduler_critical_minutes', 15);
        $this->migrator->add('operations.health_history_retention_days', 30);
        $this->migrator->add('operations.backup_enabled', false);
        $this->migrator->add('operations.backup_frequency', 'disabled');
        $this->migrator->add('operations.backup_time', '02:00');
        $this->migrator->add('operations.backup_type', 'database');
        $this->migrator->add('operations.backup_disk', 'private');
        $this->migrator->add('operations.backup_retention_count', 7);
        $this->migrator->add('operations.backup_alert_on_failure', true);
    }
};
