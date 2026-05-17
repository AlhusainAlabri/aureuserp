<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;

class PendingOutgoingCorrespondencesTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): string
    {
        return __('correspondence::correspondence.dashboard.sections.pending_outgoing');
    }

    protected function getTableQuery(): Builder
    {
        return $this->visibleCorrespondenceQuery()
            ->outgoing()
            ->whereIn('status', ['draft', 'pending_approval'])
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('reference_number')->label(__('correspondence::correspondence.reference_number')),
            TextColumn::make('subject')->label(__('correspondence::correspondence.subject'))->weight('bold')->wrap(),
            TextColumn::make('status')->label(__('correspondence::correspondence.status.label'))->formatStateUsing(fn ($state) => CorrespondenceResource::statusOptions()[$state] ?? $state)->badge(),
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
