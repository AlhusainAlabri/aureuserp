<?php

namespace Webkul\MyNotes\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NoteBoardStatus: string implements HasColor, HasIcon, HasLabel
{
    case Inbox = 'inbox';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Done = 'done';

    public function getLabel(): string
    {
        return __('my-notes::notes.board_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Inbox       => 'gray',
            self::InProgress  => 'primary',
            self::Waiting     => 'warning',
            self::Done        => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Inbox       => 'heroicon-o-inbox',
            self::InProgress  => 'heroicon-o-play',
            self::Waiting     => 'heroicon-o-clock',
            self::Done        => 'heroicon-o-check-circle',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
    }

    public static function tryFromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Inbox;
    }
}
