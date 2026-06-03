<?php

namespace App\Services\Assets;

use App\Mail\Assets\BorrowingDueReminderMail;
use App\Mail\Assets\BorrowingOverdueMail;
use App\Mail\Assets\BorrowingRequestApprovedMail;
use App\Mail\Assets\BorrowingRequestRejectedMail;
use App\Mail\Assets\BorrowingRequestSubmittedMail;
use App\Mail\Assets\BorrowingReturnedMail;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Security\Models\User;

class AssetBorrowingNotificationService
{
    public function notifySubmitted(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee', 'requestedBy']);

        $recipients = $this->managerRecipients();

        if ($borrowing->requestedBy) {
            $recipients = $recipients->push($borrowing->requestedBy)->unique('id');
        }

        $this->sendDatabase($borrowing, 'submitted', $recipients);

        if (class_exists(BorrowingRequestSubmittedMail::class)) {
            foreach ($recipients as $user) {
                Mail::to($user)->queue(new BorrowingRequestSubmittedMail($borrowing, $user));
            }
        }
    }

    public function notifyApproved(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee', 'requestedBy']);

        $recipients = collect([$borrowing->requestedBy])->filter();

        $this->sendDatabase($borrowing, 'approved', $recipients);

        if (class_exists(BorrowingRequestApprovedMail::class) && $borrowing->requestedBy) {
            Mail::to($borrowing->requestedBy)->queue(new BorrowingRequestApprovedMail($borrowing));
        }
    }

    public function notifyRejected(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee', 'requestedBy']);

        $recipients = collect([$borrowing->requestedBy])->filter();

        $this->sendDatabase($borrowing, 'rejected', $recipients);

        if (class_exists(BorrowingRequestRejectedMail::class) && $borrowing->requestedBy) {
            Mail::to($borrowing->requestedBy)->queue(new BorrowingRequestRejectedMail($borrowing));
        }
    }

    public function notifyDueReminder(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee.user']);

        $recipients = $this->managerRecipients();

        if ($borrowing->employee?->user) {
            $recipients = $recipients->push($borrowing->employee->user)->unique('id');
        }

        $this->sendDatabase($borrowing, 'due_reminder', $recipients);

        if (class_exists(BorrowingDueReminderMail::class)) {
            foreach ($recipients as $user) {
                Mail::to($user)->queue(new BorrowingDueReminderMail($borrowing, $user));
            }
        }
    }

    public function notifyOverdue(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee.user']);

        $recipients = $this->managerRecipients()
            ->merge($this->adminRecipients());

        if ($borrowing->employee?->user) {
            $recipients = $recipients->push($borrowing->employee->user);
        }

        $recipients = $recipients->unique('id');

        $this->sendDatabase($borrowing, 'overdue', $recipients, 'danger');

        if (class_exists(BorrowingOverdueMail::class)) {
            foreach ($recipients as $user) {
                Mail::to($user)->queue(new BorrowingOverdueMail($borrowing, $user));
            }
        }
    }

    public function notifyReturned(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee.user', 'requestedBy']);

        $recipients = $this->managerRecipients();

        if ($borrowing->employee?->user) {
            $recipients = $recipients->push($borrowing->employee->user);
        }

        $recipients = $recipients->unique('id');

        $this->sendDatabase($borrowing, 'returned', $recipients, 'success');

        if (class_exists(BorrowingReturnedMail::class)) {
            foreach ($recipients as $user) {
                Mail::to($user)->queue(new BorrowingReturnedMail($borrowing, $user));
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    protected function managerRecipients(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['hr_manager', 'manager', 'general_manager']))
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    protected function adminRecipients(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Admin', 'admin_manager', 'super_admin']))
            ->get();
    }

    /**
     * @param  Collection<int, User>  $users
     */
    protected function sendDatabase(AssetBorrowing $borrowing, string $key, Collection $users, string $color = 'info'): void
    {
        if ($users->isEmpty()) {
            return;
        }

        Notification::make()
            ->title(__('assets-extensions::notifications.'.$key.'.title'))
            ->body(__('assets-extensions::notifications.'.$key.'.body', [
                'asset'    => $borrowing->asset?->name ?? '—',
                'number'   => $borrowing->asset?->asset_number ?? '—',
                'employee' => $borrowing->employee?->name ?? '—',
                'due_at'   => $borrowing->due_at?->translatedFormat('Y-m-d H:i') ?? '—',
            ]))
            ->color($color)
            ->sendToDatabase($users);
    }
}
