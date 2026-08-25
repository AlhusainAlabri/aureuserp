<?php

namespace App\Services\Notifications\Channels;

use App\Services\Notifications\NotificationDefinition;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Webkul\Security\Models\User;

class DatabaseNotificationChannel
{
    /**
     * @param  Collection<int, User>  $recipients
     */
    public function send(Collection $recipients, NotificationDefinition $definition): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title($definition->title)
            ->body($definition->body);

        if ($definition->icon !== null) {
            $notification->icon($definition->icon);
        }

        if ($definition->color !== null) {
            $notification->color($definition->color);
        }

        if ($definition->actions !== []) {
            $notification->actions($definition->actions);
        }

        $notification->sendToDatabase($recipients);
    }
}
