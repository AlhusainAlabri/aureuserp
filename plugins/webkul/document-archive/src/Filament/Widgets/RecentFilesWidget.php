<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Webkul\DocumentArchive\Filament\Concerns\ConfiguresDocumentFileTable;

class RecentFilesWidget extends BaseWidget
{
    use ConfiguresDocumentFileTable;

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $placeholderHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    protected function isDashboardDocumentFileTable(): bool
    {
        return true;
    }

    public function getTableHeading(): ?string
    {
        return __('document-archive::document-archive.dashboard.stats.recent_uploads');
    }

    public function table(Table $table): Table
    {
        return $this->configureDocumentFileTable($table)
            ->query(
                $this->accessibleFilesQuery()
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->paginated(false);
    }
}
