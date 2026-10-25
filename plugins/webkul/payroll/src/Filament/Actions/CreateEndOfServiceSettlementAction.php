<?php

namespace Webkul\Payroll\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use RuntimeException;
use Webkul\Employee\Models\Employee;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Filament\Resources\EndOfServiceSettlementResource;
use Webkul\Payroll\Models\EndOfServiceSettlement;
use Webkul\Payroll\Services\FinalSettlementBuilder;

class CreateEndOfServiceSettlementAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createEndOfServiceSettlement';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('payroll::payroll.settlement.actions.create'))
            ->icon('heroicon-o-arrow-right-start-on-rectangle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('payroll::payroll.settlement.actions.create_helper'))
            ->visible(fn (Employee $record): bool => $record->departure_date !== null
                && (auth()->user()?->can('create', EndOfServiceSettlement::class) ?? false)
                && ! EndOfServiceSettlement::query()
                    ->where('employee_id', $record->id)
                    ->where('status', '!=', SettlementStatus::Cancelled->value)
                    ->exists())
            ->action(function (Employee $record, $livewire): void {
                try {
                    $settlement = app(FinalSettlementBuilder::class)->build($record);
                } catch (RuntimeException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('payroll::payroll.settlement.notifications.created'))
                    ->send();

                $livewire->redirect(EndOfServiceSettlementResource::getUrl('view', ['record' => $settlement]));
            });
    }
}
