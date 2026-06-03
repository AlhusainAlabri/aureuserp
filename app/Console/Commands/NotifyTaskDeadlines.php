<?php

namespace App\Console\Commands;

use App\Services\Projects\TaskNotificationService;
use Illuminate\Console\Command;

class NotifyTaskDeadlines extends Command
{
    protected $signature = 'tasks:notify-deadlines';

    protected $description = 'Send deadline and overdue reminders for project tasks.';

    public function handle(TaskNotificationService $notifications): int
    {
        $sent = $notifications->notifyDeadlineReminders();

        $this->info("Sent {$sent} task reminder notification(s).");

        return self::SUCCESS;
    }
}
