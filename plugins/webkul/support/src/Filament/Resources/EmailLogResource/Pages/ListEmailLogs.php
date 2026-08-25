<?php

namespace Webkul\Support\Filament\Resources\EmailLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Webkul\Support\Filament\Resources\EmailLogResource;

class ListEmailLogs extends ListRecords
{
    protected static string $resource = EmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
