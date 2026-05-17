<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;

class UrgentCorrespondencesTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected int|string|array $columnSpan = 5;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('correspondence::correspondence.dashboard.sections.urgent');
    }

    protected function getTableQuery(): Builder
    {
        return $this->visibleCorrespondenceQuery()->urgent()->where('status', '!=', 'archived')->latest()->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('reference_number')->label(__('correspondence::correspondence.reference_number')),
            TextColumn::make('subject')->label(__('correspondence::correspondence.subject'))->wrap(),
            TextColumn::make('due_date')->label(__('correspondence::correspondence.due_date'))->date(),
        ];
    }

    protected function getTableRecordActions(): array
    {
        return [
            ViewAction::make()->url(fn ($record): string => CorrespondenceResource::getUrl('view', ['record' => $record])),
        ];
    }
}
