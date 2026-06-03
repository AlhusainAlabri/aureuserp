<?php

namespace Webkul\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Assets\Enums\AssetStatus;
use Webkul\Assets\Filament\Resources\AssetResource;
use Webkul\Assets\Models\Asset;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('assets::assets.actions.create')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('assets::assets.all'))
                ->badge(Asset::query()->count()),
            'available' => Tab::make(__('assets::assets.statuses.available'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->available())
                ->badge(Asset::query()->available()->count()),
            'borrowed' => Tab::make(__('assets::assets.statuses.borrowed'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->borrowed())
                ->badge(Asset::query()->borrowed()->count()),
            'maintenance' => Tab::make(__('assets::assets.statuses.maintenance'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', AssetStatus::Maintenance))
                ->badge(Asset::query()->where('status', AssetStatus::Maintenance)->count()),
            'retired' => Tab::make(__('assets::assets.statuses.retired'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', AssetStatus::Retired))
                ->badge(Asset::query()->where('status', AssetStatus::Retired)->count()),
        ];
    }
}
