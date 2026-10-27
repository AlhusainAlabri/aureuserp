<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseAgreementResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseAgreementResource;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class ViewPurchaseAgreement extends ViewRecord
{
    use HasAgreementWorkflowActions;
    use HasRecordNavigationTabs;

    protected static string $resource = PurchaseAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->resource(static::$resource)
                ->activityPlans($this->getRecord()->activityPlans()),
            ...$this->getAgreementWorkflowActions(),
            $this->getAgreementDeleteAction(),
        ];
    }
}
