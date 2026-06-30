<?php

namespace App\Filament\Purchases\Pages;

use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource\Pages\CreateQuotation as BaseCreateQuotation;

class CreateQuotation extends BaseCreateQuotation
{
    use SetsDefaultPurchaseCurrency;

    public function mount(): void
    {
        parent::mount();

        $this->applyDefaultOmrCurrency();
    }
}
