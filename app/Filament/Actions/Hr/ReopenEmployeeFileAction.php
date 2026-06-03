<?php

namespace App\Filament\Actions\Hr;

use App\Services\Hr\EmployeeFileClosureService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Webkul\Employee\Models\Employee;

class ReopenEmployeeFileAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reopenEmployeeFile';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('hr-extensions::employee.reopen_file'))
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('hr-extensions::employee.reopen_helper'))
            ->visible(fn (Employee $record): bool => (bool) $record->is_closed
                && (auth()->user()?->can('reopen_employee_file') || auth()->user()?->hasRole('hr_manager')))
            ->schema([
                Textarea::make('reason')
                    ->label(__('hr-extensions::employee.file_status.reopen_reason'))
                    ->required()
                    ->maxLength(2000)
                    ->rows(4),
            ])
            ->action(function (Employee $record, array $data): void {
                app(EmployeeFileClosureService::class)->reopen($record, auth()->user(), $data['reason']);

                Notification::make()
                    ->success()
                    ->title(__('hr-extensions::employee.file_reopened_success'))
                    ->send();
            });
    }
}
