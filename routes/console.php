<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('system:scheduler-heartbeat')->everyMinute();
Schedule::command('system:health-check')->everyFifteenMinutes();
Schedule::command('github:check-updates')->dailyAt('07:15')->withoutOverlapping();
Schedule::command('approval:notify-sla-emails')->hourly();
Schedule::command('notifications:health')->hourly();
Schedule::command('performance:clear')->dailyAt('05:00');
Schedule::command('system:backup-clean')->dailyAt('03:30');
Schedule::command('system:backup-run --automatic')->everyFiveMinutes();
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
Schedule::command('accounting:notify-overdue-invoices')->dailyAt('09:00');
Schedule::command('inventory:run-replenishment')->dailyAt('08:45');
Schedule::command('inventory:movement-report')->weeklyOn(1, '09:00');
Schedule::command('notifications:report-daily --notify')->dailyAt('07:00');
