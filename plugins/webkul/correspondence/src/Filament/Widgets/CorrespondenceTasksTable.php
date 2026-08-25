<?php

namespace Webkul\Correspondence\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
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

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return CorrespondenceTaskService::isAvailable();
    }

    public function getTableHeading(): ?string
    {
        return __('correspondence::correspondence.dashboard.sections.tasks');
    }

    public function table(Table $table): Table
    {
        $correspondenceIds = $this->visibleCorrespondenceQuery()->pluck('id');

        return $table
            ->query(
                Task::query()
                    ->whereIn('correspondence_id', $correspondenceIds)
                    ->whereNotIn('state', [TaskState::DONE, TaskState::CANCELLED])
                    ->with(['project', 'users'])
                    ->orderBy('deadline')
                    ->limit(10)
            )
            ->columns([
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
                    ->dateTime()
                    ->color(fn (Task $record): ?string => $record->deadline?->isPast() ? 'danger' : null),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label(__('correspondence::correspondence.dashboard.actions.view_all_tasks'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(TaskResource::getUrl('index'))
                    ->color('gray')
                    ->visible(fn (): bool => class_exists(TaskResource::class)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record])),
                ViewAction::make('viewCorrespondence')
                    ->label(__('correspondence::correspondence.actions.view'))
                    ->icon('heroicon-o-envelope')
                    ->url(fn (Task $record): ?string => $record->correspondence_id
                        ? CorrespondenceResource::getUrl('view', ['record' => $record->correspondence_id])
                        : null)
                    ->visible(fn (Task $record): bool => filled($record->correspondence_id)),
            ])
            ->emptyStateHeading(__('correspondence::correspondence.tasks.empty'))
            ->emptyStateDescription(__('correspondence::correspondence.empty.no_records_description'))
            ->paginated(false);
    }
}
