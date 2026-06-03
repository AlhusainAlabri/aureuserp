<?php

namespace App\Filament\Assets\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Webkul\Assets\Models\AssetBorrowing;

class RejectBorrowingAction
{
    public static function make(): Action
    {
        return static::configure(Action::make('rejectBorrowing'));
    }

    public static function makeTableAction(): Action
    {
        return static::configure(Action::make('rejectBorrowing'));
    }

    protected static function configure(Action $action): Action
    {
        return $action
            ->label(__('assets-extensions::actions.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (AssetBorrowing $record): bool => auth()->user()?->can('reject', $record) ?? false)
            ->modalHeading(__('assets-extensions::actions.reject'))
            ->schema([
                Textarea::make('rejection_reason')
                    ->label(__('assets-extensions::fields.rejection_reason'))
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (AssetBorrowing $record, array $data): void {
                $record->reject($data['rejection_reason'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('assets-extensions::notifications.rejected.title'))
                    ->send();
            });
    }
}
