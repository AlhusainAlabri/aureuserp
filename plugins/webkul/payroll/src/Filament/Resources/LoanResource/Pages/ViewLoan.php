<?php

namespace Webkul\Payroll\Filament\Resources\LoanResource\Pages;

use App\Filament\Traits\HasApprovalActions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Filament\Resources\LoanResource;
use Webkul\Payroll\Models\Loan;

class ViewLoan extends ViewRecord
{
    use HasApprovalActions;

    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            ...$this->getApprovalActions(),
            Action::make('activate')
                ->label(__('payroll::payroll.loan.actions.activate'))
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (Loan $record): bool => auth()->user()?->can('activate', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (Loan $record): void {
                    try {
                        $record->activate();

                        Notification::make()
                            ->success()
                            ->title(__('payroll::payroll.loan.notifications.activated.title'))
                            ->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();
                    }
                }),
            Action::make('cancel')
                ->label(__('payroll::payroll.loan.actions.cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (Loan $record): bool => auth()->user()?->can('cancel', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (Loan $record): void {
                    $record->update(['status' => LoanStatus::Cancelled]);

                    Notification::make()
                        ->success()
                        ->title(__('payroll::payroll.loan.notifications.cancelled.title'))
                        ->send();
                }),
            EditAction::make()
                ->visible(fn (Loan $record): bool => $record->status === LoanStatus::Draft),
        ];
    }
}
