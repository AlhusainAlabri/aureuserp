<?php

namespace App\Filament\Contacts\Concerns;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;

trait InteractsWithContactConfigurationManagePage
{
    abstract protected static function configurationManagePageTranslationKey(): string;

    protected static function configurationManageTrans(string $key): string
    {
        return __(static::configurationManagePageTranslationKey().'.'.$key);
    }

    protected function getConfigurationCreateHeaderAction(): CreateAction
    {
        return CreateAction::make()
            ->label(static::configurationManageTrans('header-actions.create.label'))
            ->icon('heroicon-o-plus-circle')
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title(static::configurationManageTrans('header-actions.create.notification.title'))
                    ->body(static::configurationManageTrans('header-actions.create.notification.body')),
            );
    }
}
