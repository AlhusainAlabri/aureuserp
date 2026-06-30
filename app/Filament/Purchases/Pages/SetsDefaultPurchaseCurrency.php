<?php

namespace App\Filament\Purchases\Pages;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;

trait SetsDefaultPurchaseCurrency
{
    protected function applyDefaultOmrCurrency(): void
    {
        $omrCurrencyId = PurchaseOrderResourceExtensions::defaultOmrCurrencyId();

        if (! $omrCurrencyId) {
            return;
        }

        $this->form->fill([
            ...$this->form->getState(),
            'currency_id' => $omrCurrencyId,
        ]);
    }
}
