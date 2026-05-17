<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;

class EditCorrespondence extends EditRecord
{
    protected static string $resource = CorrespondenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            ViewAction::make(),
        ];
    }
}
