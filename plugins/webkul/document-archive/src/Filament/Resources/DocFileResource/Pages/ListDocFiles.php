<?php

namespace Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Webkul\DocumentArchive\Filament\Pages\DocumentManager;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Support\FilamentUrl;

class ListDocFiles extends ListRecords
{
    protected static string $resource = DocFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openManager')
                ->label(__('document-archive::document-archive.manager.open_manager'))
                ->icon('heroicon-o-folder-open')
                ->url(DocumentManager::getUrl(FilamentUrl::withLocale()))
                ->color('gray'),
            CreateAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('document-archive::document-archive.files.page_title');
    }

    public function getSubheading(): ?string
    {
        return __('document-archive::document-archive.manager.advanced_list_banner');
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
                ->badge(DocFileResource::accessibleFilesQuery()->count()),
            'private' => Tab::make(__('document-archive::document-archive.table.tabs.private'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_private', true))
                ->badge(DocFileResource::accessibleFilesQuery()->where('is_private', true)->count()),
            'expiring_soon' => Tab::make(__('document-archive::document-archive.table.tabs.expiring_soon'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->expiringSoon($expiringSoonDays))
                ->badge(DocFileResource::accessibleFilesQuery()->expiringSoon($expiringSoonDays)->count()),
            'expired' => Tab::make(__('document-archive::document-archive.table.tabs.expired'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->expired())
                ->badge(DocFileResource::accessibleFilesQuery()->expired()->count()),
        ];
    }
}
