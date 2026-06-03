<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class NotifyExpiringCivilId extends Command
{
    protected $signature = 'hr:notify-expiring-civil-id';

    protected $description = 'Send notifications for civil ID cards expiring within 30 days';

    public function handle(): int
    {
        if (! Schema::hasColumn('employees_employees', 'civil_id_expiry')) {
            $this->info('Civil ID expiry column not found.');

            return self::SUCCESS;
        }

        $today = Carbon::today();
        $threshold = $today->copy()->addDays(30);

        $employees = Employee::query()
            ->whereNotNull('civil_id_expiry')
            ->whereBetween('civil_id_expiry', [$today, $threshold])
            ->where('is_active', true)
            ->with('user')
            ->get();

        foreach ($employees as $employee) {
            foreach ($this->resolveRecipients($employee) as $user) {
                Notification::make()
                    ->title(__('hr-extensions::compliance.notifications.civil_id_title'))
                    ->body(__('hr-extensions::compliance.notifications.civil_id_body', [
                        'employee'    => $employee->name,
                        'expiry_date' => $employee->civil_id_expiry->format('Y-m-d'),
                    ]))
                    ->warning()
                    ->sendToDatabase($user);
            }
        }

        $this->info("Notified about {$employees->count()} civil ID expirations.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, User>
     */
    private function resolveRecipients(Employee $employee): array
    {
        $recipients = [];

        if ($employee->user) {
            $recipients[] = $employee->user;
        }

        foreach (User::query()->whereHas('roles', fn ($q) => $q->where('name', 'hr_manager'))->get() as $hrManager) {
            $recipients[$hrManager->id] = $hrManager;
        }

        return array_values($recipients);
    }
}
