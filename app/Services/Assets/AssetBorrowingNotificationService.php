<?php

namespace App\Services\Assets;

use App\Mail\Assets\BorrowingDueReminderMail;
use App\Mail\Assets\BorrowingOverdueMail;
use App\Mail\Assets\BorrowingRequestApprovedMail;
use App\Mail\Assets\BorrowingRequestRejectedMail;
use App\Mail\Assets\BorrowingRequestSubmittedMail;
use App\Mail\Assets\BorrowingReturnedMail;
use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Collection;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Security\Models\User;

class AssetBorrowingNotificationService
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function notifySubmitted(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee', 'requestedBy']);

        $recipients = $this->managerRecipients();

        if ($borrowing->requestedBy) {
            $recipients = $recipients->push($borrowing->requestedBy)->unique('id');
        }

        $this->dispatch($borrowing, 'submitted', $recipients, 'info', fn (User $user) => new BorrowingRequestSubmittedMail($borrowing, $user));
    }

    public function notifyApproved(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee', 'requestedBy']);

        $recipients = collect([$borrowing->requestedBy])->filter();

        $this->dispatch(
            $borrowing,
            'approved',
            $recipients,
            'info',
            fn (User $user): ?BorrowingRequestApprovedMail => $borrowing->requestedBy
                ? new BorrowingRequestApprovedMail($borrowing)
                : null,
        );
    }

    public function notifyRejected(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee', 'requestedBy']);

        $recipients = collect([$borrowing->requestedBy])->filter();

        $this->dispatch(
            $borrowing,
            'rejected',
            $recipients,
            'info',
            fn (User $user): ?BorrowingRequestRejectedMail => $borrowing->requestedBy
                ? new BorrowingRequestRejectedMail($borrowing)
                : null,
        );
    }

    public function notifyDueReminder(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee.user']);

        $recipients = $this->managerRecipients();

        if ($borrowing->employee?->user) {
            $recipients = $recipients->push($borrowing->employee->user)->unique('id');
        }

        $this->dispatch($borrowing, 'due_reminder', $recipients, 'info', fn (User $user) => new BorrowingDueReminderMail($borrowing, $user));
    }

    public function notifyOverdue(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee.user']);

        $recipients = $this->managerRecipients()
            ->merge($this->adminRecipients());

        if ($borrowing->employee?->user) {
            $recipients = $recipients->push($borrowing->employee->user);
        }

        $this->dispatch($borrowing, 'overdue', $recipients->unique('id'), 'danger', fn (User $user) => new BorrowingOverdueMail($borrowing, $user));
    }

    public function notifyReturned(AssetBorrowing $borrowing): void
    {
        $borrowing->loadMissing(['asset', 'employee.user', 'requestedBy']);

        $recipients = $this->managerRecipients();

        if ($borrowing->employee?->user) {
            $recipients = $recipients->push($borrowing->employee->user);
        }

        $this->dispatch($borrowing, 'returned', $recipients->unique('id'), 'success', fn (User $user) => new BorrowingReturnedMail($borrowing, $user));
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
     * @param  Collection<int, User>  $recipients
     */
    protected function dispatch(
        AssetBorrowing $borrowing,
        string $key,
        Collection $recipients,
        string $color,
        ?callable $mailFactory = null,
    ): void {
        if ($recipients->isEmpty()) {
            return;
        }

        $definition = NotificationDefinition::make("assets.borrowing.{$key}")
            ->recipients($recipients)
            ->title(__('assets-extensions::notifications.'.$key.'.title'))
            ->body(__('assets-extensions::notifications.'.$key.'.body', [
                'asset'    => $borrowing->asset?->name ?? '—',
                'number'   => $borrowing->asset?->asset_number ?? '—',
                'employee' => $borrowing->employee?->name ?? '—',
                'due_at'   => $borrowing->due_at?->translatedFormat('Y-m-d H:i') ?? '—',
            ]))
            ->color($color)
            ->channels([NotificationChannel::Database, NotificationChannel::Mail]);

        if ($mailFactory !== null) {
            $definition->mail(fn (User $user) => $mailFactory($user));
        } else {
            $definition->channels([NotificationChannel::Database]);
        }

        $this->notifications->dispatch($definition->build());
    }
}
