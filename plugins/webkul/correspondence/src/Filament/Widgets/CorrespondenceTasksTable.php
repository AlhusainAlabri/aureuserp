<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\Concerns\HasCorrespondenceVisibility;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Services\CorrespondenceTaskService;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Project\Models\Task;

class CorrespondenceTasksTable extends TableWidget
{
    use HasCorrespondenceVisibility;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return CorrespondenceTaskService::isAvailable();
    }

    public function getTableHeading(): ?string
    {
        return __('correspondence::correspondence.tasks.navigation');
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return __('correspondence::correspondence.tasks.empty');
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return null;
    }

    protected function getTableQuery(): Builder
    {
        $correspondenceIds = $this->visibleCorrespondenceQuery()->pluck('id');

        return Task::query()
            ->whereIn('correspondence_id', $correspondenceIds)
            ->whereNotIn('state', [TaskState::DONE, TaskState::CANCELLED])
            ->with(['project', 'users'])
            ->orderBy('deadline')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('title')
                ->label(__('correspondence::correspondence.tasks.title'))
                ->wrap(),
            TextColumn::make('correspondence_reference')
                ->label(__('correspondence::correspondence.reference_number'))
                ->state(function (Task $record): string {
                    if (! filled($record->correspondence_id)) {
                        return '-';
                    }

                    return Correspondence::query()
                        ->whereKey($record->correspondence_id)
                        ->value('reference_number') ?? '-';
                }),
            TextColumn::make('users.name')
                ->label(__('correspondence::correspondence.tasks.assignee'))
                ->listWithLineBreaks()
                ->limitList(2),
            TextColumn::make('deadline')
                ->label(__('correspondence::correspondence.tasks.deadline'))
                ->dateTime(),
        ];
    }

    protected function getTableRecordActions(): array
    {
        return [
            ViewAction::make()
                ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record])),
            ViewAction::make('viewCorrespondence')
                ->label(__('correspondence::correspondence.actions.view'))
                ->icon('heroicon-o-envelope')
                ->url(fn (Task $record): ?string => $record->correspondence_id
                    ? CorrespondenceResource::getUrl('view', ['record' => $record->correspondence_id])
                    : null)
                ->visible(fn (Task $record): bool => filled($record->correspondence_id)),
        ];
    }
}
