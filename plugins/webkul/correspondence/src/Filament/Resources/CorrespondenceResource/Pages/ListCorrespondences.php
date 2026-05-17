<?php

namespace Webkul\Correspondence\Filament\Resources\CorrespondenceResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;

class ListCorrespondences extends ListRecords
{
    protected static string $resource = CorrespondenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'outgoing' => Tab::make(__('correspondence::correspondence.outgoing'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->outgoing()),
            'incoming' => Tab::make(__('correspondence::correspondence.incoming'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->incoming()),
        ];
    }
}
