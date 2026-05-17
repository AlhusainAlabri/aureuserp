<?php

namespace Webkul\DocumentArchive\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Webkul\DocumentArchive\Models\DocFile;

class RecentFilesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return __('document-archive::document-archive.dashboard.stats.recent_uploads');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DocFile::query()->with('folder')->orderByDesc('created_at')->limit(10)
            )
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('document-archive::document-archive.fields.reference_number')),
                TextColumn::make('name')
                    ->label(__('document-archive::document-archive.fields.name'))
                    ->searchable(),
                TextColumn::make('folder.name')
                    ->label(__('document-archive::document-archive.fields.folder')),
                TextColumn::make('file_size')
                    ->label(__('document-archive::document-archive.fields.file_size'))
                    ->formatStateUsing(fn (DocFile $record): string => $record->getFileSizeForHumans()),
                TextColumn::make('created_at')
                    ->label(__('document-archive::document-archive.fields.created_at'))
                    ->dateTime(),
            ])
            ->paginated(false);
    }
}
