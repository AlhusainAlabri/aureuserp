<?php

use App\Console\Commands\NotificationHealthCheck;
use App\Console\Commands\ReportDailyNotifications;
use App\Mail\Notifications\NotificationDailyReportMail;
use App\Services\Notifications\NotificationOperationsReport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Support\Models\EmailLog;

it('builds a notification operations report from available tables', function (): void {
    if (! Schema::hasTable('email_logs')) {
        $this->markTestSkipped('email_logs table not available.');
    }

    EmailLog::query()->create([
        'recipient_email' => 'queued@example.com',
        'recipient_name'  => 'Queued User',
        'subject'         => 'Queued',
        'status'          => 'queued',
        'sent_at'         => now(),
    ]);

    EmailLog::query()->create([
        'recipient_email' => 'failed@example.com',
        'recipient_name'  => 'Failed User',
        'subject'         => 'Failed',
        'status'          => 'failed',
        'error_message'   => 'SMTP timeout',
        'sent_at'         => now(),
    ]);

    $report = app(NotificationOperationsReport::class)->build(now()->subDay());

    expect($report['email_logs']['total'])->toBeGreaterThanOrEqual(2)
        ->and($report['email_logs']['failed'])->toBeGreaterThanOrEqual(1);
});

it('runs the daily notification report command in dry-run mode', function (): void {
    Artisan::call('notifications:report-daily', ['--dry-run' => true]);

    expect(Artisan::output())->toContain('Notification operations report');
});

it('queues the daily report mail when notify is enabled', function (): void {
    Mail::fake();
    Config::set('notifications.operations.report_recipients', 'ops@example.com');

    Artisan::call('notifications:report-daily', ['--notify' => true]);

    Mail::assertQueued(NotificationDailyReportMail::class);
});

it('returns a non-zero exit code from the health check when thresholds are breached', function (): void {
    Config::set('notifications.operations.failed_jobs_threshold', 0);

    $exitCode = Artisan::call('notifications:health');

    expect($exitCode)->toBe(NotificationHealthCheck::FAILURE);
});

it('returns success from the health check when thresholds are normal', function (): void {
    Config::set('notifications.operations.failed_jobs_threshold', 99999);
    Config::set('notifications.operations.failed_email_rate_threshold', 1);
    Config::set('notifications.operations.queue_backlog_threshold', 99999);

    $exitCode = Artisan::call('notifications:health');

    expect($exitCode)->toBe(ReportDailyNotifications::SUCCESS);
});
