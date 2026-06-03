<?php

namespace App\Filament\Assets\Actions;

use App\Filament\Assets\Support\SignatureField;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\Assets\Models\AssetBorrowing;

class ApproveBorrowingAction
{
    public static function make(): Action
    {
        return static::configure(Action::make('approveBorrowing'));
    }

    public static function makeTableAction(): Action
    {
        return static::configure(Action::make('approveBorrowing'));
    }

    protected static function configure(Action $action): Action
    {
        return $action
            ->label(__('assets-extensions::actions.approve'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (AssetBorrowing $record): bool => auth()->user()?->can('approve', $record) ?? false)
            ->modalHeading(__('assets-extensions::actions.approve'))
            ->schema([
                SignatureField::make('signature'),
            ])
            ->action(function (AssetBorrowing $record, array $data): void {
                $record->approve($data['signature'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('assets-extensions::notifications.approved.title'))
                    ->send();
            });
    }
}
