<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('approval:process-sla')->hourly();
Schedule::command('meetings:notify-overdue-tasks')->dailyAt('08:00');
Schedule::command('tasks:notify-deadlines')->dailyAt('08:00');
Schedule::command('assets:notify-overdue-borrowings')->dailyAt('08:30');
Schedule::command('assets:notify-due-soon')->dailyAt('08:00');
Schedule::command('correspondence:notify-overdue')->dailyAt('09:00');
Schedule::command('hr:notify-expiring-documents')->dailyAt('08:00');
Schedule::command('hr:notify-expiring-training-certificates')->dailyAt('08:00');
Schedule::command('hr:notify-expiring-civil-id')->dailyAt('08:00');
Schedule::command('hr:notify-expiring-contracts')->dailyAt('08:00');
Schedule::command('hr:notify-pending-leave-approvals')->dailyAt('08:15');
Schedule::command('hr:remind-self-assessments')->monthlyOn(25, '08:00');
Schedule::command('hr:remind-self-assessments')->lastDayOfMonth('20:00');
Schedule::command('purchases:remind-receipts')->dailyAt('09:00');
Schedule::command('submissions:remind-unresolved')->weeklyOn(1, '09:00');
Schedule::command('notes:send-reminders')->everyFiveMinutes();
Schedule::command('document-archive:cleanup-share-links')->dailyAt('06:00');
Schedule::command('document-archive:archive-expired')->dailyAt('06:00');
Schedule::command('projects:performance-report')->weeklyOn(1, '08:00');
Schedule::command('inventory:notify-low-stock')->dailyAt('08:30');
Schedule::command('inventory:run-replenishment')->dailyAt('08:45');
Schedule::command('inventory:movement-report')->weeklyOn(1, '09:00');
