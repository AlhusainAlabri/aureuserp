<?php

namespace Webkul\DocumentArchive\Filament\Tables;

use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Webkul\DocumentArchive\Services\DocumentTagService;

class DocumentTagTable
{
    public static function tagsColumn(): ViewColumn
    {
        return ViewColumn::make('tags_display')
            ->label(__('document-archive::document-archive.fields.tags'))
            ->view('document-archive::components.document-tags-table-column')
            ->toggleable();
    }

    public static function tagsFilter(): SelectFilter
    {
        return SelectFilter::make('tags')
            ->label(__('document-archive::document-archive.fields.tags'))
            ->multiple()
            ->searchable()
            ->preload()
            ->options(fn (): array => app(DocumentTagService::class)->optionsForSelect())
            ->query(function (Builder $query, array $data): Builder {
                $values = $data['values'] ?? [];

                if (! is_array($values) || $values === []) {
                    return $query;
                }

                return app(DocumentTagService::class)->applyTagFilter($query, $values);
            });
    }
}
