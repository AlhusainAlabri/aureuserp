<?php

namespace App\Filament\Resources\DashboardShortcutResource\Pages;

use App\Filament\Resources\DashboardShortcutResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDashboardShortcut extends EditRecord
{
    protected static string $resource = DashboardShortcutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
