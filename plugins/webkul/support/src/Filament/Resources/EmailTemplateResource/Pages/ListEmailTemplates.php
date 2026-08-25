<?php

namespace Webkul\Support\Filament\Resources\EmailTemplateResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Support\Filament\Resources\EmailTemplateResource;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('support::filament/resources/email-template.pages.list.create'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
