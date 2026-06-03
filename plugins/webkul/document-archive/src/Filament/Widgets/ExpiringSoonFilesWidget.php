<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Webkul\DocumentArchive\Filament\Concerns\ConfiguresDocumentFileTable;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Support\FilamentUrl;

class ExpiringSoonFilesWidget extends BaseWidget
{
    use ConfiguresDocumentFileTable;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        $days = (int) config('document-archive.expiring_soon_days', 7);

        return __('document-archive::document-archive.dashboard.stats.expiring_soon_heading', [
            'days' => $days,
        ]);
    }

    public function table(Table $table): Table
    {
        $days = (int) config('document-archive.expiring_soon_days', 7);

        return $this->configureDocumentFileTable($table)
            ->query(
                $this->accessibleFilesQuery()
                    ->expiringSoon($days)
                    ->orderBy('expiry_date')
            )
            ->paginated([10, 25, 50])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('document-archive::document-archive.dashboard.stats.view_all_expiring'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(DocFileResource::getUrl('index', FilamentUrl::withLocale(['tab' => 'expiring_soon'])))
                    ->link(),
            ])
            ->emptyStateHeading(__('document-archive::document-archive.dashboard.empty.expiring_soon'));
    }
}
