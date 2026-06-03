<?php

namespace App\Filament\Projects\Widgets;

use App\Enums\Projects\TaskPriorityLevel;
use App\Filament\Projects\Concerns\InteractsWithTaskFilters;
use App\Services\Projects\TaskStatePresenter;
use App\Support\FilamentUrl;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Schema;
use Webkul\FullCalendar\Filament\Widgets\FullCalendarWidget;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Filament\Resources\TaskResource;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\Task;
use Webkul\Support\Models\CalendarLeave;
use Webkul\TimeOff\Models\Leave;

class OperationsCalendarWidget extends FullCalendarWidget
{
    use HasMeetingVisibility;
    use InteractsWithTaskFilters;

    public bool $showProjectTasks = true;

    public bool $showMeetings = true;

    public bool $showMeetingTasks = true;

    public bool $showLeave = true;

    public bool $showMilestones = true;

    public bool $showHolidays = true;

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('tasks.calendar.title');
    }

    public function config(): array
    {
        return [
            'initialView'   => 'dayGridMonth',
            'locale'        => app()->getLocale(),
            'direction'     => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            'firstDay'      => 0,
            'headerToolbar' => [
                'left'   => 'prev,next today',
                'center' => 'title',
                'right'  => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'buttonText' => [
                'today'        => __('meetings::meetings.calendar.today'),
                'dayGridMonth' => __('meetings::meetings.calendar.month'),
                'timeGridWeek' => __('meetings::meetings.calendar.week'),
                'timeGridDay'  => __('meetings::meetings.calendar.day'),
                'listWeek'     => __('meetings::meetings.calendar.list'),
            ],
            'height'           => 'auto',
            'aspectRatio'      => 1.7,
            'displayEventTime' => true,
        ];
    }

    protected function headerActions(): array
    {
        return [
            Action::make('filters')
                ->label(__('tasks.filters.title'))
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->schema([
                    Toggle::make('showProjectTasks')->label(__('tasks.filters.show_project_tasks'))->default($this->showProjectTasks),
                    Toggle::make('showMeetings')->label(__('tasks.filters.show_meetings'))->default($this->showMeetings),
                    Toggle::make('showMeetingTasks')->label(__('tasks.filters.show_meeting_tasks'))->default($this->showMeetingTasks),
                    Toggle::make('showLeave')->label(__('tasks.filters.show_leave'))->default($this->showLeave),
                    Toggle::make('showMilestones')->label(__('tasks.filters.show_milestones'))->default($this->showMilestones),
                    Toggle::make('showHolidays')->label(__('tasks.filters.show_holidays'))->default($this->showHolidays),
                    ...$this->taskFilterSchema(),
                ])
                ->fillForm(fn (): array => [
                    'showProjectTasks'   => $this->showProjectTasks,
                    'showMeetings'       => $this->showMeetings,
                    'showMeetingTasks'   => $this->showMeetingTasks,
                    'showLeave'          => $this->showLeave,
                    'showMilestones'     => $this->showMilestones,
                    'showHolidays'       => $this->showHolidays,
                    'filterEmployeeId'   => $this->filterEmployeeId,
                    'filterDepartmentId' => $this->filterDepartmentId,
                    'filterProjectId'    => $this->filterProjectId,
                    'filterCategoryId'   => $this->filterCategoryId,
                    'filterPriority'     => $this->filterPriority,
                ])
                ->action(function (array $data): void {
                    $this->showProjectTasks = (bool) ($data['showProjectTasks'] ?? true);
                    $this->showMeetings = (bool) ($data['showMeetings'] ?? true);
                    $this->showMeetingTasks = (bool) ($data['showMeetingTasks'] ?? true);
                    $this->showLeave = (bool) ($data['showLeave'] ?? true);
                    $this->showMilestones = (bool) ($data['showMilestones'] ?? true);
                    $this->showHolidays = (bool) ($data['showHolidays'] ?? true);
                    $this->filterEmployeeId = $data['filterEmployeeId'] ?? null;
                    $this->filterDepartmentId = $data['filterDepartmentId'] ?? null;
                    $this->filterProjectId = $data['filterProjectId'] ?? null;
                    $this->filterCategoryId = $data['filterCategoryId'] ?? null;
                    $this->filterPriority = $data['filterPriority'] ?? null;
                    $this->refreshRecords();
                }),
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return [
            ...($this->showProjectTasks ? $this->projectTaskEvents($fetchInfo) : []),
            ...($this->showMeetings ? $this->meetingEvents($fetchInfo) : []),
            ...($this->showMeetingTasks ? $this->meetingTaskEvents($fetchInfo) : []),
            ...($this->showLeave ? $this->leaveEvents($fetchInfo) : []),
            ...($this->showMilestones ? $this->milestoneEvents($fetchInfo) : []),
            ...($this->showHolidays ? $this->holidayEvents($fetchInfo) : []),
        ];
    }

    protected function projectTaskEvents(array $fetchInfo): array
    {
        if (! Schema::hasTable('projects_tasks')) {
            return [];
        }

        $query = Task::query()
            ->whereNull('parent_id')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', Carbon::parse($fetchInfo['start'])->toDateString())
            ->whereDate('deadline', '<=', Carbon::parse($fetchInfo['end'])->toDateString())
            ->whereNotIn('state', [TaskState::CANCELLED]);

        $this->applyTaskFilters($query);

        return $query->get()->map(function (Task $task): array {
            $priority = TaskPriorityLevel::tryFrom((string) ($task->priority_level ?? 'medium'));
            $color = match ($priority) {
                TaskPriorityLevel::Urgent => '#EF4444',
                TaskPriorityLevel::High   => '#F59E0B',
                TaskPriorityLevel::Low    => '#9CA3AF',
                default                   => '#3B82F6',
            };

            if (TaskStatePresenter::isOverdue($task)) {
                $color = '#DC2626';
            }

            return [
                'id'              => 'project-task-'.$task->id,
                'title'           => $task->title,
                'start'           => $task->deadline?->toDateString(),
                'allDay'          => true,
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'extendedProps'   => [
                    'eventType' => 'project_task',
                    'url'       => FilamentUrl::appendLocaleToUrl(TaskResource::getUrl('view', ['record' => $task])),
                    'isPast'    => TaskStatePresenter::isOverdue($task),
                ],
            ];
        })->all();
    }

    protected function meetingEvents(array $fetchInfo): array
    {
        if (! class_exists(Meeting::class) || ! Schema::hasTable('meetings')) {
            return [];
        }

        return $this->visibleMeetingsQuery()
            ->confirmed()
            ->whereBetween('meeting_date', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(function (Meeting $meeting): array {
                $color = match ($meeting->type) {
                    'internal'  => '#3B82F6',
                    'external'  => '#8B5CF6',
                    'emergency' => '#EF4444',
                    'board'     => '#10B981',
                    default     => '#6B7280',
                };

                return [
                    'id'              => 'meeting-'.$meeting->id,
                    'title'           => $meeting->meeting_number.' — '.$meeting->title,
                    'start'           => $meeting->meeting_date?->toIso8601String(),
                    'end'             => $meeting->meeting_date?->copy()->addMinutes($meeting->duration_minutes ?? 60)->toIso8601String(),
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => '#ffffff',
                    'extendedProps'   => [
                        'eventType' => 'meeting',
                        'url'       => FilamentUrl::appendLocaleToUrl(MeetingResource::getUrl('view', ['record' => $meeting])),
                    ],
                ];
            })
            ->all();
    }

    protected function meetingTaskEvents(array $fetchInfo): array
    {
        if (! class_exists(MeetingTask::class) || ! Schema::hasTable('meeting_tasks')) {
            return [];
        }

        return $this->visibleTasksQuery()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [Carbon::parse($fetchInfo['start'])->toDateString(), Carbon::parse($fetchInfo['end'])->toDateString()])
            ->get()
            ->map(fn (MeetingTask $task): array => [
                'id'              => 'meeting-task-'.$task->id,
                'title'           => __('meetings::meetings.calendar.task_title', ['title' => $task->title]),
                'start'           => $task->due_date?->toDateString(),
                'allDay'          => true,
                'backgroundColor' => $task->status === 'completed' ? '#6B7280' : '#F59E0B',
                'borderColor'     => $task->status === 'completed' ? '#6B7280' : '#F59E0B',
                'extendedProps'   => [
                    'eventType' => 'meeting_task',
                ],
            ])
            ->all();
    }

    protected function leaveEvents(array $fetchInfo): array
    {
        if (! class_exists(Leave::class) || ! Schema::hasTable('time_off_leaves')) {
            return [];
        }

        return Leave::query()
            ->where('request_date_from', '<=', $fetchInfo['end'])
            ->where('request_date_to', '>=', $fetchInfo['start'])
            ->with('holidayStatus', 'user')
            ->get()
            ->map(fn (Leave $leave): array => [
                'id'              => 'leave-'.$leave->id,
                'title'           => ($leave->holidayStatus?->name ?? '').' '.($leave->user?->name ?? ''),
                'start'           => $leave->request_date_from,
                'end'             => $leave->request_date_to ? Carbon::parse($leave->request_date_to)->addDay()->toDateString() : null,
                'allDay'          => true,
                'display'         => 'background',
                'backgroundColor' => $leave->holidayStatus?->color ?? '#94A3B8',
                'extendedProps'   => [
                    'eventType' => 'leave',
                ],
            ])
            ->all();
    }

    protected function milestoneEvents(array $fetchInfo): array
    {
        if (! class_exists(Milestone::class) || ! Schema::hasTable('projects_milestones')) {
            return [];
        }

        return Milestone::query()
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(fn (Milestone $milestone): array => [
                'id'              => 'milestone-'.$milestone->id,
                'title'           => $milestone->name,
                'start'           => $milestone->deadline?->toDateString(),
                'allDay'          => true,
                'backgroundColor' => '#8B5CF6',
                'borderColor'     => '#7C3AED',
                'extendedProps'   => [
                    'eventType' => 'milestone',
                ],
            ])
            ->all();
    }

    protected function holidayEvents(array $fetchInfo): array
    {
        if (! class_exists(CalendarLeave::class) || ! Schema::hasTable('calendar_leaves')) {
            return [];
        }

        return CalendarLeave::query()
            ->whereBetween('date_from', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(fn (CalendarLeave $holiday): array => [
                'id'              => 'holiday-'.$holiday->id,
                'title'           => $holiday->name,
                'start'           => $holiday->date_from,
                'end'             => $holiday->date_to ? Carbon::parse($holiday->date_to)->addDay()->toDateString() : null,
                'allDay'          => true,
                'display'         => 'background',
                'backgroundColor' => '#D1D5DB',
                'extendedProps'   => [
                    'eventType' => 'holiday',
                ],
            ])
            ->all();
    }
}
