<?php

namespace App\Filament\Resources\MyTimeOff\Pages;

use App\Filament\Actions\Hr\AcceptSubstituteAction;
use App\Filament\Actions\Hr\DeclineSubstituteAction;
use Webkul\TimeOff\Filament\Clusters\MyTime\Resources\MyTimeOffResource\Pages\ViewMyTimeOff as BaseViewMyTimeOff;

class ViewMyTimeOff extends BaseViewMyTimeOff
{
    protected function getHeaderActions(): array
    {
        return [
            AcceptSubstituteAction::make(),
            DeclineSubstituteAction::make(),
            ...parent::getHeaderActions(),
        ];
    }
}
