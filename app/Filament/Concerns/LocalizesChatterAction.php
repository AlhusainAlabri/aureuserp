<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Webkul\Chatter\Filament\Actions\ChatterAction;

trait LocalizesChatterAction
{
    protected function localizeChatterAction(Action $action): void
    {
        if (! $action instanceof ChatterAction) {
            return;
        }

        $action
            ->label(__('chatter::filament/resources/actions/chatter-action.title'))
            ->tooltip(__('chatter::filament/resources/actions/chatter-action.title'));
    }

    /**
     * @param  array<Action>  $actions
     * @return array<Action>
     */
    protected function localizeChatterInHeaderActions(array $actions): array
    {
        foreach ($actions as $action) {
            $this->localizeChatterAction($action);
        }

        return $actions;
    }
}
