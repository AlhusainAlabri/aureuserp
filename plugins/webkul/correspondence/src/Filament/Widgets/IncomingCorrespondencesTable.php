<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;

class IncomingCorrespondencesTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('correspondence::correspondence.dashboard.sections.incoming');
    }

    protected function getTableQuery(): Builder
    {
        return $this->visibleCorrespondenceQuery()->incoming()->latest('received_at')->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('reference_number')->label(__('correspondence::correspondence.reference_number')),
            TextColumn::make('sender_name')->label(__('correspondence::correspondence.sender_name')),
            TextColumn::make('subject')->label(__('correspondence::correspondence.subject'))->weight('bold')->wrap(),
            TextColumn::make('received_at')->label(__('correspondence::correspondence.received_at'))->date(),
            TextColumn::make('priority')->label(__('correspondence::correspondence.priority.label'))->formatStateUsing(fn ($state) => CorrespondenceResource::priorityOptions()[$state] ?? $state)->badge(),
        ];
    }

    protected function getTableRecordActions(): array
    {
        return [
            ViewAction::make()->url(fn ($record): string => CorrespondenceResource::getUrl('view', ['record' => $record])),
        ];
    }
}
