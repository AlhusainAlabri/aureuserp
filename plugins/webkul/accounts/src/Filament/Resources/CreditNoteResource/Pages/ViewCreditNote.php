<?php

namespace Webkul\Account\Filament\Resources\CreditNoteResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Webkul\Account\Filament\Resources\CreditNoteResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions as BaseActions;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ViewInvoice as ViewRecord;

class ViewCreditNote extends ViewRecord
{
    protected static string $resource = CreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return $this->adaptActions(parent::getHeaderActions());
    }

    /**
     * Drop the invoice-only actions and swap in the credit note preview, also inside action groups.
     *
     * @param  array<int|string, Action|ActionGroup>  $actions
     * @return array<int, Action|ActionGroup>
     */
    protected function adaptActions(array $actions): array
    {
        return collect($actions)
            ->map(function ($action) {
                if ($action instanceof ActionGroup) {
                    return $action->actions($this->adaptActions($action->getActions()));
                }

                return $action;
            })
            ->reject(fn ($action) => ! $action instanceof ActionGroup && in_array($action->getName(), [
                'customers.invoice.set-as-checked',
                'customers.invoice.credit-note',
            ]))
            ->map(function ($action) {
                if (! $action instanceof ActionGroup && $action->getName() == 'customers.invoice.preview') {
                    return BaseActions\PreviewAction::make()
                        ->modalHeading(__('accounts::filament/resources/credit-note/pages/view-credit-note.header-actions.preview.modal-heading'))
                        ->setTemplate('accounts::credit-note/actions/preview.index');
                }

                return $action;
            })
            ->values()
            ->all();
    }
}
