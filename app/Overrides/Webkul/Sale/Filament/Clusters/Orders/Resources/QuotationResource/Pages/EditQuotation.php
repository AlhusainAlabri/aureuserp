<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use App\Filament\Concerns\LocalizesChatterAction;
use Filament\Actions\Action;

class EditQuotation extends CoreEditQuotation
{
    use LocalizesChatterAction;

    protected function configureAction(Action $action): void
    {
        $this->localizeChatterAction($action);

        if (is_callable([parent::class, 'configureAction'])) {
            parent::configureAction($action);
        }
    }
}
