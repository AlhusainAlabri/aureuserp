<?php

namespace Webkul\Assets\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Models\Asset;
use Webkul\Assets\Models\AssetBorrowing;
use Webkul\Employee\Models\Employee;

class BorrowAction
{
    public static function make(): Action
    {
        return Action::make('borrow')
            ->label(__('assets::assets.actions.borrow'))
            ->icon('heroicon-o-arrow-right-circle')
            ->color('primary')
            ->visible(fn (Asset $record): bool => auth()->user()?->can('borrow', $record) ?? false)
            ->modalHeading(__('assets::assets.actions.borrow'))
            ->schema([
                Select::make('employee_id')
                    ->label(__('assets::assets.fields.employee'))
                    ->options(fn (): array => Schema::hasTable('employees_employees')
                        ? Employee::query()->orderBy('name')->pluck('name', 'id')->all()
                        : [])
                    ->searchable()
                    ->searchPrompt(__('assets::assets.fields.employee_search'))
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
            ->action(function (Asset $record, array $data): void {
                AssetBorrowing::createDirectCheckout(
                    $record,
                    (int) $data['employee_id'],
                    Carbon::parse($data['due_at']),
                    $data['notes'] ?? null,
                );

                Notification::make()
                    ->success()
                    ->title(__('assets::assets.notifications.borrowed.title'))
                    ->body(__('assets::assets.notifications.borrowed.body', [
                        'name' => $record->name,
                    ]))
                    ->send();
            });
    }
}
