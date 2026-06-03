<?php

namespace App\Filament\Actions\Hr;

use App\Services\Hr\LeaveSubstituteService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\TimeOff\Models\Leave;

class AcceptSubstituteAction
{
    public static function make(): Action
    {
        return Action::make('accept_substitute')
            ->label(__('hr-extensions::leave.actions.accept_substitute'))
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (Leave $record): bool => self::canAccept($record))
            ->requiresConfirmation()
            ->action(function (Leave $record): void {
                app(LeaveSubstituteService::class)->accept($record);

                Notification::make()
                    ->title(__('hr-extensions::leave.substitute_accepted'))
                    ->success()
                    ->send();
            });
    }

    public static function canAccept(Leave $record): bool
    {
        if (! $record->substitute_employee_id || $record->substitute_accepted_at) {
            return false;
        }

        $employee = auth()->user()?->employee;

        return $employee && (int) $employee->id === (int) $record->substitute_employee_id;
    }
}
