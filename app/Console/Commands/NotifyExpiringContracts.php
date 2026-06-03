<?php

namespace App\Console\Commands;

use App\Models\Hr\EmployeeContract;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class NotifyExpiringContracts extends Command
{
    protected $signature = 'hr:notify-expiring-contracts';

    protected $description = 'Send notifications for employment contracts expiring within 30 days';

    public function handle(): int
    {
        if (! Schema::hasTable('employee_contracts')) {
            $this->info('Employee contracts table not found.');

            return self::SUCCESS;
        }

        $today = Carbon::today();
        $threshold = $today->copy()->addDays(30);

        $contracts = EmployeeContract::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $threshold])
            ->where(function ($query) use ($today) {
                $query->whereNull('notified_at')
                    ->orWhereDate('notified_at', '<', $today);
            })
            ->with(['employee.user'])
            ->get();

        foreach ($contracts as $contract) {
            foreach ($this->resolveRecipients($contract) as $user) {
                Notification::make()
                    ->title(__('hr-extensions::contract.notifications.expiring_title'))
                    ->body(__('hr-extensions::contract.notifications.expiring_body', [
                        'employee' => $contract->employee->name,
                        'end_date' => $contract->end_date->format('Y-m-d'),
                    ]))
                    ->warning()
                    ->sendToDatabase($user);
            }

            $contract->update(['notified_at' => $today]);
        }

        $this->info("Notified about {$contracts->count()} expiring contracts.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, User>
     */
    private function resolveRecipients(EmployeeContract $contract): array
    {
        $recipients = [];

        if ($contract->employee?->user) {
            $recipients[] = $contract->employee->user;
        }

        foreach (User::query()->whereHas('roles', fn ($q) => $q->where('name', 'hr_manager'))->get() as $hrManager) {
            $recipients[$hrManager->id] = $hrManager;
        }

        return array_values($recipients);
    }
}
