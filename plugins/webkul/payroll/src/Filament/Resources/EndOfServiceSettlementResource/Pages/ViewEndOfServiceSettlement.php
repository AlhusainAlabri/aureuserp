<?php

namespace Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource\Pages;

use App\Filament\Traits\HasApprovalActions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource;
use Webkul\Payroll\Models\EndOfServiceSettlement;

class ViewEndOfServiceSettlement extends ViewRecord
{
    use HasApprovalActions;

    protected static string $resource = EndOfServiceSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getApprovalActions(),
            Action::make('markPaid')
                ->label(__('payroll::payroll.settlement.actions.mark_paid'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (EndOfServiceSettlement $record): bool => auth()->user()?->can('markPaid', $record) ?? false)
                ->requiresConfirmation()
                ->modalDescription(__('payroll::payroll.settlement.actions.mark_paid_helper'))
                ->action(function (EndOfServiceSettlement $record): void {
                    try {
                        $record->markAsPaid();

                        Notification::make()
                            ->success()
                            ->title(__('payroll::payroll.settlement.notifications.paid'))
                            ->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();
                    }
                }),
            Action::make('cancel')
                ->label(__('payroll::payroll.settlement.actions.cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (EndOfServiceSettlement $record): bool => auth()->user()?->can('cancel', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (EndOfServiceSettlement $record): void {
                    try {
                        $record->cancel();

                        Notification::make()
                            ->success()
                            ->title(__('payroll::payroll.settlement.notifications.cancelled'))
                            ->send();
                    } catch (RuntimeException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();
                    }
                }),
            EditAction::make()
                ->visible(fn (EndOfServiceSettlement $record): bool => $record->isEditable()),
        ];
    }
}
