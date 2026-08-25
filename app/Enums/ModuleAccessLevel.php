<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ModuleAccessLevel: string implements HasColor, HasLabel
{
    case None = 'none';
    case View = 'view';
    case Standard = 'standard';
    case Full = 'full';

    public function getLabel(): string
    {
        return __('role-management.module_access.levels.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None     => 'gray',
            self::View     => 'info',
            self::Standard => 'warning',
            self::Full     => 'success',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
