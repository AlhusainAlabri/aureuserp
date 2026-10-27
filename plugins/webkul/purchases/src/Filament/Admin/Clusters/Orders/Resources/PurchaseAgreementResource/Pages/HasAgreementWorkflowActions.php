<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseAgreementResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Webkul\Purchase\Enums\RequisitionState;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseAgreementResource;
use Webkul\Purchase\Models\Requisition;

/**
 * Workflow buttons shared by the view and edit pages of a purchase agreement.
 */
trait HasAgreementWorkflowActions
{
    /**
     * @return array<Action>
     */
    protected function getAgreementWorkflowActions(): array
    {
        return [
            Action::make('confirm')
                ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.confirm.label'))
                ->color('primary')
                ->action(function () {
                    $record = $this->getRecord();

                    if (! PurchaseAgreementResource::canBeConfirmed($record)) {
                        Notification::make()
                            ->danger()
                            ->title(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.confirm.notification.error.title'))
                            ->body(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.confirm.notification.error.body'))
                            ->send();

                        return;
                    }

                    $record->update([
                        'state' => RequisitionState::CONFIRMED,
                    ]);

                    $this->fillForm();
                })
                ->visible(fn () => $this->getRecord()->state == RequisitionState::DRAFT),
            Action::make('close')
                ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.close.label'))
                ->color('primary')
                ->action(function () {
                    $record = $this->getRecord();

                    if (! PurchaseAgreementResource::canBeClosed($record)) {
                        Notification::make()
                            ->danger()
                            ->title(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.close.notification.warning.title'))
                            ->body(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.close.notification.warning.body'))
                            ->send();

                        return;
                    }

                    $this->getRecord()->update([
                        'state' => RequisitionState::CLOSED,
                    ]);

                    $this->fillForm();
                })
                ->visible(fn () => $this->getRecord()->state == RequisitionState::CONFIRMED),
            Action::make('cancelRecord')
                ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.cancel.label'))
                ->color('gray')
                ->action(function () {
                    $this->getRecord()->update([
                        'state' => RequisitionState::CANCELED,
                    ]);

                    $this->fillForm();
                })
                ->visible(fn () => ! in_array($this->getRecord()->state, [
                    RequisitionState::CLOSED,
                    RequisitionState::CANCELED,
                ])),
            Action::make('print')
                ->label(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.print.label'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function (Requisition $record) {
                    $pdf = Pdf::loadView('purchases::filament.admin.clusters.orders.purchase-agreements.print', [
                        'records' => collect([$record]),
                    ]);

                    $pdf->setPaper('a4', 'portrait');

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Purchase Agreement-'.str_replace('/', '_', $record->name).'.pdf');
                }),
        ];
    }

    protected function getAgreementDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->hidden(fn (): bool => in_array($this->getRecord()->state, [RequisitionState::CONFIRMED, RequisitionState::CLOSED]))
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.delete.notification.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.header-actions.delete.notification.body')),
            );
    }
}
