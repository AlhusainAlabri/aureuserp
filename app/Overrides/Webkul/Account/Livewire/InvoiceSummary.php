<?php

namespace Webkul\Account\Livewire;

use Filament\Actions\Action;

/**
 * Reconciling and unlinking write several records; both run in one transaction so a refusal half-way (for example by
 * the accounting period lock) leaves nothing behind.
 */
class InvoiceSummary extends CoreInvoiceSummary
{
    public function reconcileAction(): Action
    {
        return parent::reconcileAction()
            ->label(__('invoices-extensions::summary.reconcile'))
            ->databaseTransaction();
    }

    public function unReconcileAction(): Action
    {
        return parent::unReconcileAction()
            ->label(__('invoices-extensions::summary.unlink'))
            ->databaseTransaction();
    }
}
