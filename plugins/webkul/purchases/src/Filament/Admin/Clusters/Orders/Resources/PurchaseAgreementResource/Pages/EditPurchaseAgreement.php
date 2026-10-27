<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseAgreementResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseAgreementResource;
use Webkul\Support\Filament\Concerns\HasRepeaterColumnManager;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class EditPurchaseAgreement extends EditRecord
{
    use HasAgreementWorkflowActions;
    use HasRecordNavigationTabs;
    use HasRepeaterColumnManager;

    protected static string $resource = PurchaseAgreementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.notification.title'))
            ->body(__('purchases::filament/admin/clusters/orders/resources/purchase-agreement/pages/edit-purchase-agreement.notification.body'));
    }

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
