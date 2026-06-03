<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Filament\Widgets\Concerns\InteractsWithMeetingDashboardFilters;
use Webkul\Meetings\Models\MeetingTask;

class MeetingTasksTable extends TableWidget
{
    use HasMeetingVisibility;
    use InteractsWithMeetingDashboardFilters;
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 7;

    protected ?string $pollingInterval = null;

    public function getTableHeading(): ?string
    {
        return __('meetings::meetings.dashboard.sections.my_tasks');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->filteredTasksQuery()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->with('meeting')
                    ->orderBy('due_date')
                    ->limit(6)
            )
            ->columns([
                TextColumn::make('priority')
                    ->label(__('meetings::meetings.fields.priority'))
                    ->formatStateUsing(fn (?string $state): string => MeetingResource::priorityOptions()[$state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('title')
                    ->label(__('meetings::meetings.fields.task_title'))
                    ->wrap(),
                TextColumn::make('meeting.meeting_number')
                    ->label(__('meetings::meetings.fields.meeting_number'))
                    ->url(fn (MeetingTask $record): string => MeetingResource::getUrl('view', ['record' => $record->meeting])),
                TextColumn::make('due_date')
                    ->label(__('meetings::meetings.fields.due_date'))
                    ->date()
                    ->color(fn (MeetingTask $record): ?string => $record->due_date?->isPast() ? 'danger' : null),
                SelectColumn::make('status')
                    ->label(__('meetings::meetings.fields.status'))
                    ->options(MeetingResource::taskStatusOptions()),
            ])
            ->headerActions([
                Action::make('viewAllMeetings')
                    ->label(__('meetings::meetings.dashboard.actions.view_all'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(MeetingResource::getUrl('index'))
                    ->color('gray'),
            ])
            ->emptyStateHeading(__('meetings::meetings.empty.no_tasks'))
            ->emptyStateDescription(__('meetings::meetings.empty.no_tasks_description'))
            ->paginated(false);
    }
}
