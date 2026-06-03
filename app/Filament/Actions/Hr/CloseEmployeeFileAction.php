<?php

namespace App\Filament\Actions\Hr;

use App\Enums\Hr\ClosureReason;
use App\Services\Hr\EmployeeFileClosureService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Webkul\Employee\Models\Employee;

class CloseEmployeeFileAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'closeEmployeeFile';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('hr-extensions::employee.close_file'))
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('hr-extensions::employee.close_helper'))
            ->visible(fn (Employee $record): bool => ! $record->is_closed
                && auth()->user()?->can('close_employee_file'))
            ->schema([
                Select::make('reason')
                    ->label(__('hr-extensions::employee.file_status.reason'))
                    ->options(ClosureReason::class)
                    ->required()
                    ->native(false),
                Textarea::make('notes')
                    ->label(__('hr-extensions::employee.file_status.notes'))
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->action(function (Employee $record, array $data): void {
                app(EmployeeFileClosureService::class)->close(
                    $record,
                    auth()->user(),
                    $data['reason'],
                    $data['notes'] ?? null,
                );

                Notification::make()
                    ->success()
                    ->title(__('hr-extensions::employee.file_closed_success'))
                    ->send();
            });
    }
}
