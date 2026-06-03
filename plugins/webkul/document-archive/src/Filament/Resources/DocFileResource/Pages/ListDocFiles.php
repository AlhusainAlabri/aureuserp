<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Models\DocFile;

class ListDocFiles extends ListRecords
{
    protected static string $resource = DocFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        $tab = request()->query('tab');

        if (filled($tab) && array_key_exists($tab, $this->getTabs())) {
            return $tab;
        }

        return parent::getDefaultActiveTab();
    }

    public function getTabs(): array
    {
        $expiringSoonDays = (int) config('document-archive.expiring_soon_days', 7);

        return [
            'all' => Tab::make(__('document-archive::document-archive.table.tabs.all'))
                ->badge(DocFile::query()->count()),
            'private' => Tab::make(__('document-archive::document-archive.table.tabs.private'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_private', true))
                ->badge(DocFile::query()->where('is_private', true)->count()),
            'expiring_soon' => Tab::make(__('document-archive::document-archive.table.tabs.expiring_soon'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->expiringSoon($expiringSoonDays))
                ->badge(DocFile::query()->expiringSoon($expiringSoonDays)->count()),
            'expired' => Tab::make(__('document-archive::document-archive.table.tabs.expired'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->expired())
                ->badge(DocFile::query()->expired()->count()),
        ];
    }
}
