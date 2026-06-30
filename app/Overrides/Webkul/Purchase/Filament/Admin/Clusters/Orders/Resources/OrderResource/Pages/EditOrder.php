<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages;

use App\Filament\Concerns\LocalizesChatterAction;
use Filament\Actions\Action;

class EditOrder extends CoreEditOrder
{
    use LocalizesChatterAction;

    protected function configureAction(Action $action): void
    {
        $this->localizeChatterAction($action);

        parent::configureAction($action);
    }
}
