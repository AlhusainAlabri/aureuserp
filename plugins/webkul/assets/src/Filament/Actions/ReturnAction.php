<?php

namespace Webkul\Assets\Filament\Actions;

use App\Filament\Assets\Support\SignatureField;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\Assets\Models\Asset;

class ReturnAction
{
    public static function make(): Action
    {
        return Action::make('return')
            ->label(__('assets::assets.actions.return'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->visible(fn (Asset $record): bool => auth()->user()?->can('returnAsset', $record) ?? false)
            ->modalHeading(__('assets::assets.actions.return'))
            ->modalDescription(__('assets::assets.actions.return_confirmation'))
            ->schema([
                SignatureField::make('signature'),
            ])
            ->action(function (Asset $record, array $data): void {
                $borrowing = $record->activeBorrowing;

                if ($borrowing === null) {
                    Notification::make()
                        ->danger()
                        ->title(__('assets::assets.notifications.no_active_borrowing.title'))
                        ->send();

                    return;
                }

                $borrowing->markReturned($data['signature'] ?? null);

                Notification::make()
                    ->success()
                    ->title(__('assets::assets.notifications.returned.title'))
                    ->body(__('assets::assets.notifications.returned.body', [
                        'name' => $record->name,
                    ]))
                    ->send();
            });
    }
}
