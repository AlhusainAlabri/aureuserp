<?php

namespace App\Console\Commands;

use App\Models\Hr\EmployeeTraining;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class NotifyExpiringTrainingCertificates extends Command
{
    protected $signature = 'hr:notify-expiring-training-certificates';

    protected $description = 'Send notifications for training certificates expiring within 60 days';

    public function handle(): int
    {
        if (! Schema::hasTable('employee_trainings')) {
            $this->warn('employee_trainings table not found.');

            return self::SUCCESS;
        }

        $today = Carbon::today();
        $threshold = $today->copy()->addDays(60);

        $trainings = EmployeeTraining::query()
            ->whereNotNull('certificate_expiry_date')
            ->whereBetween('certificate_expiry_date', [$today, $threshold])
            ->where(function ($query) use ($today): void {
                $query->whereNull('certificate_notified_at')
                    ->orWhereDate('certificate_notified_at', '<', $today);
            })
            ->with(['employee.user', 'creator'])
            ->get();

        foreach ($trainings as $training) {
            $recipients = $this->resolveRecipients($training);

            foreach ($recipients as $user) {
                Notification::make()
                    ->title(__('hr-extensions::training.notifications.expiring.title'))
                    ->body(__('hr-extensions::training.notifications.expiring.body', [
                        'employee' => $training->employee?->name ?? '—',
                        'course'   => $training->course_name,
                        'date'     => $training->certificate_expiry_date?->format('Y-m-d') ?? '—',
                    ]))
                    ->warning()
                    ->sendToDatabase($user);
            }

            $training->update(['certificate_notified_at' => $today]);
        }

        $this->info("Notified about {$trainings->count()} expiring training certificates.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, User>
     */
    private function resolveRecipients(EmployeeTraining $training): array
    {
        $recipients = [];

        if ($training->employee?->user) {
            $recipients[$training->employee->user->id] = $training->employee->user;
        }

        $hrManagers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'hr_manager');
            })
            ->get();

        foreach ($hrManagers as $hrManager) {
            $recipients[$hrManager->id] = $hrManager;
        }

        return array_values($recipients);
    }
}
