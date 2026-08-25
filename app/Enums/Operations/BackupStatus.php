<?php

namespace App\Enums\Operations;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BackupStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __('operations.backups.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending   => 'gray',
            self::Running   => 'info',
            self::Completed => 'success',
            self::Failed    => 'danger',
        };
    }
}
