<?php

namespace App\Console\Commands;

use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Security\Models\User;

class RemindUnresolvedSubmissions extends Command
{
    protected $signature = 'submissions:remind-unresolved';

    protected $description = 'Send weekly reminders for unresolved submissions';

    public function handle(): void
    {
        $openSubmissions = EmployeeSubmission::open()
            ->where('created_at', '<', now()->subDays(7))
            ->orderBy('created_at')
            ->get();

        if ($openSubmissions->isEmpty()) {
            $this->info('No unresolved submissions older than 7 days.');

            return;
        }

        $count = $openSubmissions->count();
        $oldest = $openSubmissions->first();
        $daysOld = (int) $oldest->created_at->diffInDays(now());

        $hrManagers = User::role('hr_manager')->get();
        $generalManagers = User::role('general_manager')->get();

        foreach ($hrManagers->merge($generalManagers)->unique('id') as $manager) {
            Notification::make()
                ->title(__('employees::filament/resources/submission.notifications.unresolved.title', ['count' => $count]))
                ->body(__('employees::filament/resources/submission.notifications.unresolved.body', [
                    'ticket' => $oldest->ticket_number,
                    'days'   => $daysOld,
                ]))
                ->warning()
                ->sendToDatabase($manager);
        }

        $this->info("Sent reminders for {$count} unresolved submissions.");
    }
}
