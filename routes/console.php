<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('approval:process-sla')->hourly();
Schedule::command('meetings:notify-overdue-tasks')->dailyAt('08:00');
Schedule::command('correspondence:notify-overdue')->dailyAt('09:00');
