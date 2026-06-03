<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;

class CorrespondenceApprovalsTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected int|string|array $columnSpan = 5;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('correspondence::correspondence.dashboard.sections.my_approvals');
    }

    protected function getTableQuery(): Builder
    {
        return $this->pendingApprovalsQuery()->latest()->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('reference_number')->label(__('correspondence::correspondence.reference_number')),
            TextColumn::make('subject')->label(__('correspondence::correspondence.subject'))->wrap(),
            TextColumn::make('creator.name')->label(__('correspondence::correspondence.creator')),
        ];
    }

    protected function getTableRecordActions(): array
    {
        return [
            ViewAction::make()->url(fn ($record): string => CorrespondenceResource::getUrl('view', ['record' => $record])),
        ];
    }
}
