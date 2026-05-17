<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Webkul\Employee\Models\EmployeeDocument;
use Webkul\Security\Models\User;

class NotifyExpiringEmployeeDocuments extends Command
{
    protected $signature = 'hr:notify-expiring-documents';

    protected $description = 'Send notifications for employee documents expiring within 30 days';

    public function handle(): int
    {
        $today = Carbon::today();
        $threshold = $today->copy()->addDays(30);

        $documents = EmployeeDocument::query()
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $threshold])
            ->where(function ($query) use ($today) {
                $query->whereNull('notified_at')
                    ->orWhereDate('notified_at', '<', $today);
            })
            ->with(['employee.user', 'creator'])
            ->get();

        foreach ($documents as $document) {
            $recipients = $this->resolveRecipients($document);

            foreach ($recipients as $user) {
                Notification::make()
                    ->title(__('employees::filament/resources/employee/relation-manager/document.notifications.expiring-soon.title'))
                    ->body(__('employees::filament/resources/employee/relation-manager/document.notifications.expiring-soon.body', [
                        'document_name'  => $document->document_name,
                        'employee_name'  => $document->employee->name,
                        'expiry_date'    => $document->expiry_date->format('Y-m-d'),
                    ]))
                    ->warning()
                    ->sendToDatabase($user);
            }

            $document->update(['notified_at' => $today]);
        }

        $this->info("Notified about {$documents->count()} expiring documents.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, User>
     */
    private function resolveRecipients(EmployeeDocument $document): array
    {
        $recipients = [];

        if ($document->employee?->user) {
            $recipients[] = $document->employee->user;
        }

        $hrManagers = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'hr_manager');
            })
            ->get();

        foreach ($hrManagers as $hrManager) {
            $recipients[$hrManager->id] = $hrManager;
        }

        return array_values($recipients);
    }
}
