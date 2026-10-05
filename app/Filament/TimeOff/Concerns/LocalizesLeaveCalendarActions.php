<?php

namespace App\Filament\TimeOff\Concerns;

use Filament\Actions\Action;
use Webkul\FullCalendar\Filament\Actions\CreateAction;
use Webkul\FullCalendar\Filament\Actions\DeleteAction;
use Webkul\FullCalendar\Filament\Actions\EditAction;

trait LocalizesLeaveCalendarActions
{
    protected function localizedLeaveModelLabel(): string
    {
        return __('time-off::filament/clusters/management/resources/time-off.model-label');
    }

    /**
     * @param  array<int, Action>  $actions
     * @return array<int, Action>
     */
    protected function withLocalizedLeaveModelLabel(array $actions): array
    {
        return array_map(function (Action $action): Action {
            if (
                $action instanceof CreateAction
                || $action instanceof EditAction
                || $action instanceof DeleteAction
            ) {
                return $action->modelLabel($this->localizedLeaveModelLabel());
            }

            return $action;
        }, $actions);
    }
}
