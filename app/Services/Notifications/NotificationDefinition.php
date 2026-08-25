<?php

namespace App\Services\Notifications;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Collection;
use Webkul\Security\Models\User;

final class NotificationDefinition
{
    /**
     * @param  Collection<int, User>|array<int, User>  $recipients
     * @param  array<int, string>  $channels
     * @param  array<int, Action>  $actions
     * @param  Closure(User): (?Mailable)  $mailFactory
     * @param  Closure(User): bool  $databaseEnabled
     * @param  Closure(User): bool  $emailEnabled
     */
    public function __construct(
        public readonly string $key,
        public readonly Collection|array $recipients,
        public readonly string $title,
        public readonly string $body,
        public readonly array $channels = [
            NotificationChannel::Database,
            NotificationChannel::Mail,
        ],
        public readonly ?string $icon = null,
        public readonly ?string $color = null,
        public readonly array $actions = [],
        public readonly ?Closure $mailFactory = null,
        public readonly ?Closure $databaseEnabled = null,
        public readonly ?Closure $emailEnabled = null,
        public readonly bool $required = false,
    ) {}

    public static function make(string $key): NotificationDefinitionBuilder
    {
        return new NotificationDefinitionBuilder($key);
    }
}
