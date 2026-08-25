<?php

namespace Webkul\Account\Livewire;

use Filament\Actions\Action;

class InvoiceSummary extends CoreInvoiceSummary
{
    public function reconcileAction(): Action
    {
        return parent::reconcileAction()
            ->label(__('invoices-extensions::summary.reconcile'));
    }

    public function unReconcileAction(): Action
    {
        return parent::unReconcileAction()
            ->label(__('invoices-extensions::summary.unlink'));
    }
}
