<?php

namespace App\Services\Notifications;

use App\Services\Notifications\Channels\DatabaseNotificationChannel;
use App\Services\Notifications\Channels\MailNotificationChannel;
use Illuminate\Support\Collection;
use Webkul\Security\Models\User;

class NotificationDispatcher
{
    public function __construct(
        private readonly DatabaseNotificationChannel $databaseChannel,
        private readonly MailNotificationChannel $mailChannel,
        private readonly NotificationPreferenceService $preferences,
    ) {}

    public function dispatch(NotificationDefinition $definition): void
    {
        $recipients = $this->normalizeRecipients($definition->recipients);

        if ($recipients->isEmpty()) {
            return;
        }

        if ($this->usesChannel($definition, NotificationChannel::Database)) {
            $databaseRecipients = $recipients->filter(
                fn (User $user): bool => $this->isDatabaseEnabled($definition, $user),
            );

            if ($databaseRecipients->isNotEmpty()) {
                $this->databaseChannel->send($databaseRecipients, $definition);
            }
        }

        if ($this->usesChannel($definition, NotificationChannel::Mail)) {
            foreach ($recipients as $user) {
                if ($this->isEmailEnabled($definition, $user)) {
                    $this->mailChannel->send($user, $definition);
                }
            }
        }
    }

    /** @param  Collection<int, User>|array<int, User>  $recipients */
    protected function normalizeRecipients(Collection|array $recipients): Collection
    {
        return collect($recipients)
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique('id')
            ->values();
    }

    protected function usesChannel(NotificationDefinition $definition, string $channel): bool
    {
        return in_array($channel, $definition->channels, true);
    }

    protected function isDatabaseEnabled(NotificationDefinition $definition, User $user): bool
    {
        if ($definition->required) {
            return true;
        }

        if ($definition->databaseEnabled !== null) {
            return (bool) ($definition->databaseEnabled)($user);
        }

        return $this->preferences->isDatabaseEnabled($user, $definition->key);
    }

    protected function isEmailEnabled(NotificationDefinition $definition, User $user): bool
    {
        if ($definition->required && $definition->mailFactory !== null) {
            return filled($user->email);
        }

        if ($definition->emailEnabled !== null) {
            return (bool) ($definition->emailEnabled)($user);
        }

        return $this->preferences->isEmailEnabled($user, $definition->key);
    }
}
