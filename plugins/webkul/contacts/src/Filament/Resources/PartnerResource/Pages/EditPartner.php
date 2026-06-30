<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\EditPartner as BaseEditPartner;

class EditPartner extends BaseEditPartner
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        $actions = parent::getHeaderActions();

        foreach ($actions as $action) {
            if ($action instanceof ChatterAction) {
                $action
                    ->label(__('chatter::filament/resources/actions/chatter-action.title'))
                    ->tooltip(__('chatter::filament/resources/actions/chatter-action.title'));
            }
        }

        return $actions;
    }
}
