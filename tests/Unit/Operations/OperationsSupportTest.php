<?php

use App\Enums\Operations\BackupFrequency;
use App\Enums\Operations\BackupType;
use App\Support\Operations\AutomaticBackupScheduler;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Support\Carbon;

it('returns operations defaults when settings are missing', function (): void {
    $settings = OperationsSettingsResolver::resolve();

    expect($settings->monitoring_enabled)->toBeTrue()
        ->and($settings->backup_enabled)->toBeFalse()
        ->and($settings->backup_frequency)->toBe(BackupFrequency::Disabled);
});

it('skips automatic backup outside the configured window', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-25 10:00:00'));

    $settings = OperationsSettingsResolver::defaults();
    $settings->backup_enabled = true;
    $settings->backup_frequency = BackupFrequency::Daily;
    $settings->backup_time = '02:00';
    $settings->backup_type = BackupType::Database;

    expect(AutomaticBackupScheduler::shouldRunNow($settings))->toBeFalse();

    Carbon::setTestNow();
});

it('allows automatic backup inside the configured window on scheduled days', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-25 02:02:00'));

    $settings = OperationsSettingsResolver::defaults();
    $settings->backup_enabled = true;
    $settings->backup_frequency = BackupFrequency::Daily;
    $settings->backup_time = '02:00';
    $settings->backup_type = BackupType::Database;

    expect(AutomaticBackupScheduler::shouldRunNow($settings))->toBeTrue();

    Carbon::setTestNow();
});
