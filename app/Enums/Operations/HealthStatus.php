<?php

namespace App\Enums\Operations;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum HealthStatus: string implements HasColor, HasIcon, HasLabel
{
    case Healthy = 'healthy';
    case Warning = 'warning';
    case Critical = 'critical';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return __('operations.health.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Healthy  => 'success',
            self::Warning  => 'warning',
            self::Critical => 'danger',
            self::Unknown  => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Healthy   => 'heroicon-o-check-circle',
            self::Warning   => 'heroicon-o-exclamation-triangle',
            self::Critical  => 'heroicon-o-x-circle',
            self::Unknown   => 'heroicon-o-question-mark-circle',
        };
    }

    public function severity(): int
    {
        return match ($this) {
            self::Healthy  => 0,
            self::Unknown  => 1,
            self::Warning  => 2,
            self::Critical => 3,
        };
    }

    public static function worst(HealthStatus ...$statuses): self
    {
        return collect($statuses)
            ->sortByDesc(fn (self $status): int => $status->severity())
            ->first() ?? self::Unknown;
    }
}
