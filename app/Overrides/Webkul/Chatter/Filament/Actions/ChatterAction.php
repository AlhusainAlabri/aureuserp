<?php

namespace Webkul\Chatter\Filament\Actions;

use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ChatterAction extends CoreChatterAction
{
    protected function configureModal(): void
    {
        $this
            ->slideOver()
            ->label(__('chatter::filament/resources/actions/chatter-action.title'))
            ->tooltip(__('chatter::filament/resources/actions/chatter-action.title'))
            ->icon(Heroicon::ChatBubbleLeftRight)
            ->modalIcon(Heroicon::ChatBubbleLeftRight)
            ->modalIconColor('primary')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->closeModalByEscaping()
            ->modalHeading(__('chatter::filament/resources/actions/chatter-action.title'))
            ->badge(fn ($record): int => $record->unRead()->count())
            ->modalContent(fn ($record) => $this->renderModalContent($record));
    }
}
