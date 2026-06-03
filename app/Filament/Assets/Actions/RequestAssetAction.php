<?php

namespace App\Filament\Assets\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;

class RequestAssetAction
{
    public static function make(): Action
    {
        return Action::make('requestAsset')
            ->label(__('assets-extensions::actions.request_asset'))
            ->icon('heroicon-o-hand-raised')
            ->color('primary')
            ->visible(fn (): bool => auth()->user()?->employee !== null
                && auth()->user()?->can('request_borrow_assets_asset'))
            ->modalHeading(__('assets-extensions::actions.request_asset'))
            ->schema([
                Select::make('asset_id')
                    ->label(__('assets::assets.models.asset'))
                    ->options(fn (): array => Asset::query()->available()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                DateTimePicker::make('due_at')
                    ->label(__('assets::assets.fields.due_at'))
                    ->native(false)
                    ->seconds(false)
                    ->minDate(now())
                    ->required(),
                Textarea::make('notes')
                    ->label(__('assets::assets.fields.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $employee = auth()->user()?->employee;

                if ($employee === null) {
                    Notification::make()
                        ->danger()
                        ->title(__('assets-extensions::requests.errors.no_employee'))
                        ->send();

                    return;
                }

                $asset = Asset::query()->findOrFail($data['asset_id']);

                if (! auth()->user()?->can('requestBorrow', $asset)) {
                    Notification::make()
                        ->danger()
                        ->title(__('assets-extensions::requests.errors.unauthorized'))
                        ->send();

                    return;
                }

                AssetBorrowing::submitRequest(
                    $asset,
                    $employee,
                    $data['due_at'],
                    $data['notes'] ?? null,
                );

                Notification::make()
                    ->success()
                    ->title(__('assets-extensions::notifications.submitted.title'))
                    ->send();
            });
    }
}
