<?php

namespace App\Services\Hr;

use App\Mail\Hr\HrAlertMail;
use App\Services\Notifications\NotificationChannel;
use App\Services\Notifications\NotificationDedupService;
use App\Services\Notifications\NotificationDefinition;
use App\Services\Notifications\NotificationDispatcher;
use Filament\Actions\Action;
use Webkul\Security\Models\User;

class HrNotificationService
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
        private readonly NotificationDedupService $dedup,
    ) {}

    public function dispatchAlert(
        User $recipient,
        string $notificationKey,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?string $dedupKey = null,
    ): bool {
        $dispatch = function () use ($recipient, $notificationKey, $title, $body, $actionUrl): void {
            $actions = [];

            if ($actionUrl) {
                $actions[] = Action::make('view')
                    ->label(__('approval-notifications.actions.view'))
                    ->url($actionUrl);
            }

            $this->notifications->dispatch(
                NotificationDefinition::make($notificationKey)
                    ->recipients([$recipient])
                    ->title($title)
                    ->body($body)
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->actions($actions)
                    ->channels([NotificationChannel::Database, NotificationChannel::Mail])
                    ->mail(fn () => new HrAlertMail(
                        recipient: $recipient,
                        subjectLine: $title,
                        body: $body,
                        actionUrl: $actionUrl,
                        actionLabel: __('approval-notifications.actions.view'),
                    ))
                    ->build(),
            );
        };

        if ($dedupKey === null) {
            $dispatch();

            return true;
        }

        return $this->dedup->rememberIfNotSent($dedupKey, $dispatch);
    }
}
