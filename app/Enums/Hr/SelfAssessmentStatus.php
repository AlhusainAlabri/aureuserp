<?php

namespace App\Enums\Hr;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SelfAssessmentStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';

    public function getLabel(): string
    {
        return __('hr-extensions::self_assessment.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Submitted => 'warning',
            self::Reviewed  => 'success',
        };
    }
}
