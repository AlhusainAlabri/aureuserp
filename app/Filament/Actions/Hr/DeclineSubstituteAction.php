<?php

namespace App\Filament\Actions\Hr;

use App\Services\Hr\LeaveSubstituteService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\TimeOff\Models\Leave;

class DeclineSubstituteAction
{
    public static function make(): Action
    {
        return Action::make('decline_substitute')
            ->label(__('hr-extensions::leave.actions.decline_substitute'))
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (Leave $record): bool => self::canDecline($record))
            ->requiresConfirmation()
            ->action(function (Leave $record): void {
                app(LeaveSubstituteService::class)->decline($record);

                Notification::make()
                    ->title(__('hr-extensions::leave.substitute_declined'))
                    ->success()
                    ->send();
            });
    }

    public static function canDecline(Leave $record): bool
    {
        if (! $record->substitute_employee_id || $record->substitute_accepted_at || $record->substitute_declined_at) {
            return false;
        }

        $employee = auth()->user()?->employee;

        return $employee && (int) $employee->id === (int) $record->substitute_employee_id;
    }
}
