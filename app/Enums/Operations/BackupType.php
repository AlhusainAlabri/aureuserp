<?php

namespace App\Enums\Operations;

use Filament\Support\Contracts\HasLabel;

enum BackupType: string implements HasLabel
{
    case Database = 'database';
    case Files = 'files';
    case Full = 'full';

    public function getLabel(): string
    {
        return __('operations.backups.types.'.$this->value);
    }
}
