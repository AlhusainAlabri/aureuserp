<?php

namespace App\Services\Notifications;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Collection;
use Webkul\Security\Models\User;

final class NotificationDefinitionBuilder
{
    /** @var Collection<int, User>|array<int, User> */
    private Collection|array $recipients = [];

    private ?string $title = null;

    private ?string $body = null;

    /** @var array<int, string> */
    private array $channels = [
        NotificationChannel::Database,
        NotificationChannel::Mail,
    ];

    private ?string $icon = null;

    private ?string $color = null;

    /** @var array<int, Action> */
    private array $actions = [];

    /** @var Closure(User): (?Mailable)|null */
    private ?Closure $mailFactory = null;

    /** @var Closure(User): bool|null */
    private ?Closure $databaseEnabled = null;

    /** @var Closure(User): bool|null */
    private ?Closure $emailEnabled = null;

    private bool $required = false;

    public function __construct(
        private readonly string $key,
    ) {}

    /** @param  Collection<int, User>|array<int, User>  $recipients */
    public function recipients(Collection|array $recipients): self
    {
        $this->recipients = $recipients;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /** @param  array<int, string>  $channels */
    public function channels(array $channels): self
    {
        $this->channels = $channels;

        return $this;
    }

    public function icon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /** @param  array<int, Action>  $actions */
    public function actions(array $actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    /** @param  Closure(User): (?Mailable)  $mailFactory */
    public function mail(Closure $mailFactory): self
    {
        $this->mailFactory = $mailFactory;

        return $this;
    }

    /** @param  Closure(User): bool  $databaseEnabled */
    public function databaseWhen(Closure $databaseEnabled): self
    {
        $this->databaseEnabled = $databaseEnabled;

        return $this;
    }

    /** @param  Closure(User): bool  $emailEnabled */
    public function emailWhen(Closure $emailEnabled): self
    {
        $this->emailEnabled = $emailEnabled;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function build(): NotificationDefinition
    {
        if ($this->title === null || $this->body === null) {
            throw new \InvalidArgumentException("Notification [{$this->key}] requires title and body.");
        }

        return new NotificationDefinition(
            key: $this->key,
            recipients: $this->recipients,
            title: $this->title,
            body: $this->body,
            channels: $this->channels,
            icon: $this->icon,
            color: $this->color,
            actions: $this->actions,
            mailFactory: $this->mailFactory,
            databaseEnabled: $this->databaseEnabled,
            emailEnabled: $this->emailEnabled,
            required: $this->required,
        );
    }

    public function dispatch(?NotificationDispatcher $dispatcher = null): void
    {
        ($dispatcher ?? app(NotificationDispatcher::class))->dispatch($this->build());
    }
}
