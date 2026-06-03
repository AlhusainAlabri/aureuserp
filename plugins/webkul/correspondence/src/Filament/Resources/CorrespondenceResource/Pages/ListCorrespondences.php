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
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->outgoing()
                    ->where('status', '!=', 'archived')),
            'incoming' => Tab::make(__('correspondence::correspondence.incoming'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->incoming()
                    ->where('status', '!=', 'archived')
                    ->orderByDesc('received_at')),
            'archived' => Tab::make(__('correspondence::correspondence.tabs.archived'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'archived')),
        ];
    }
}
