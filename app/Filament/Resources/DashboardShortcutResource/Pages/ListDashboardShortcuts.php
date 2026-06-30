<?php

namespace App\Filament\Resources\DashboardShortcutResource\Pages;

use App\Filament\Resources\DashboardShortcutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDashboardShortcuts extends ListRecords
{
    protected static string $resource = DashboardShortcutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
