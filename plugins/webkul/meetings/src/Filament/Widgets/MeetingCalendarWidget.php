<?php

namespace Webkul\Meetings\Filament\Widgets;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\FullCalendar\Filament\Widgets\FullCalendarWidget;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingTask;
use Webkul\Security\Models\User;
use Webkul\TimeOff\Models\Leave;

class MeetingCalendarWidget extends FullCalendarWidget
{
    use HasMeetingVisibility;

    public Model|string|null $model = null;

    public bool $showMeetings = true;

    public bool $showTasks = true;

    public bool $showTimeOff = true;

    public bool $showUnconfirmedMeetings = false;

    public string $typeFilter = 'all';

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return __('meetings::meetings.navigation.calendar');
    }

    public function config(): array
    {
        return [
            'initialView'      => 'timeGridWeek',
            'locale'           => app()->getLocale(),
            'direction'        => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            'firstDay'         => 0,
            'headerToolbar'    => [
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
            'eventClassNames'  => 'function(info) { return info.event.extendedProps.isPast ? ["opacity-60"] : []; }',
        ];
    }

    protected function headerActions(): array
    {
        return [
            Action::make('createMeeting')
                ->label(__('meetings::meetings.actions.create'))
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('create_meetings_meeting') ?? false)
                ->schema([
                    TextInput::make('title')->label(__('meetings::meetings.fields.title'))->required(),
                    Select::make('type')->label(__('meetings::meetings.fields.type'))->options(MeetingResource::typeOptions())->required(),
                    DateTimePicker::make('meeting_date')->label(__('meetings::meetings.fields.meeting_date'))->required(),
                    Select::make('chair_person_id')->label(__('meetings::meetings.fields.chair_person'))->options(fn () => User::query()->pluck('name', 'id'))->searchable()->required(),
                ])
                ->action(fn (array $data): Meeting => Meeting::query()->create($data + [
                    'company_id' => auth()->user()?->default_company_id,
                ])),
            Action::make('filters')
                ->label(__('meetings::meetings.calendar.filters'))
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->schema([
                    Select::make('showMeetings')
                        ->label(__('meetings::meetings.calendar.show_meetings'))
                        ->options([1 => __('meetings::meetings.yes'), 0 => __('meetings::meetings.no')])
                        ->default($this->showMeetings ? 1 : 0),
                    Toggle::make('showUnconfirmedMeetings')
                        ->label(__('meetings::meetings.calendar.show_unconfirmed'))
                        ->helperText(__('meetings::meetings.calendar.show_unconfirmed_hint'))
                        ->default($this->showUnconfirmedMeetings),
                    Select::make('showTasks')
                        ->label(__('meetings::meetings.calendar.show_tasks'))
                        ->options([1 => __('meetings::meetings.yes'), 0 => __('meetings::meetings.no')])
                        ->default($this->showTasks ? 1 : 0),
                    Select::make('showTimeOff')
                        ->label(__('meetings::meetings.calendar.show_time_off'))
                        ->options([1 => __('meetings::meetings.yes'), 0 => __('meetings::meetings.no')])
                        ->default($this->showTimeOff ? 1 : 0),
                    Select::make('typeFilter')
                        ->label(__('meetings::meetings.fields.type'))
                        ->options(['all' => __('meetings::meetings.all'), ...MeetingResource::typeOptions()])
                        ->default($this->typeFilter),
                ])
                ->action(function (array $data): void {
                    $this->showMeetings = (bool) $data['showMeetings'];
                    $this->showUnconfirmedMeetings = (bool) ($data['showUnconfirmedMeetings'] ?? false);
                    $this->showTasks = (bool) $data['showTasks'];
                    $this->showTimeOff = (bool) $data['showTimeOff'];
                    $this->typeFilter = $data['typeFilter'] ?? 'all';
                    $this->refreshRecords();
                }),
        ];
    }

    protected function viewAction(): Action
    {
        return Action::make('view')
            ->label(__('meetings::meetings.actions.view'))
            ->modalSubmitAction(false)
            ->schema([
                Section::make(__('meetings::meetings.infolist.sections.details'))
                    ->schema([
                        TextEntry::make('title')->label(__('meetings::meetings.fields.title')),
                    ]),
            ]);
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return [
            ...($this->showMeetings ? $this->meetingEvents($fetchInfo) : []),
            ...($this->showTasks ? $this->taskEvents($fetchInfo) : []),
            ...($this->showTimeOff ? $this->timeOffEvents($fetchInfo) : []),
        ];
    }

    protected function meetingEvents(array $fetchInfo): array
    {
        return $this->visibleMeetingsQuery()
            ->when(
                $this->showUnconfirmedMeetings,
                fn (Builder $query): Builder => $query->active(),
                fn (Builder $query): Builder => $query->confirmed(),
            )
            ->when($this->typeFilter !== 'all', fn (Builder $query): Builder => $query->where('type', $this->typeFilter))
            ->whereBetween('meeting_date', [$fetchInfo['start'], $fetchInfo['end']])
            ->get()
            ->map(function (Meeting $meeting): array {
                $colors = $this->meetingEventColors($meeting);

                return [
                    'id'              => 'meeting-'.$meeting->id,
                    'title'           => $this->meetingEventTitle($meeting),
                    'start'           => $meeting->meeting_date?->toIso8601String(),
                    'end'             => $meeting->meeting_date?->copy()->addMinutes($meeting->duration_minutes ?? 60)->toIso8601String(),
                    'backgroundColor' => $colors['backgroundColor'],
                    'borderColor'     => $colors['borderColor'],
                    'textColor'       => $colors['textColor'],
                    'classNames'      => $colors['classNames'],
                    'extendedProps'   => [
                        'eventType' => 'meeting',
                        'status'    => $meeting->status,
                        'isPast'    => $meeting->meeting_date?->isPast(),
                        'url'       => MeetingResource::getUrl('view', ['record' => $meeting]),
                    ],
                ];
            })
            ->all();
    }

    protected function meetingEventTitle(Meeting $meeting): string
    {
        if (! $this->showUnconfirmedMeetings || $meeting->isConfirmed()) {
            return "{$meeting->meeting_number} — {$meeting->title}";
        }

        $statusLabel = MeetingResource::statusOptions()[$meeting->status] ?? $meeting->status;

        return "[{$statusLabel}] {$meeting->meeting_number} — {$meeting->title}";
    }

    /**
     * @return array{backgroundColor: string, borderColor: string, textColor: string, classNames: array<int, string>}
     */
    protected function meetingEventColors(Meeting $meeting): array
    {
        if ($meeting->isConfirmed()) {
            $color = $this->eventColor($meeting->type);

            return [
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'textColor'       => '#ffffff',
                'classNames'      => [],
            ];
        }

        return match ($meeting->status) {
            'approved' => [
                'backgroundColor' => '#BFDBFE',
                'borderColor'     => '#3B82F6',
                'textColor'       => '#1E3A8A',
                'classNames'      => ['border-dashed'],
            ],
            'pending_approval' => [
                'backgroundColor' => '#FDE68A',
                'borderColor'     => '#F59E0B',
                'textColor'       => '#78350F',
                'classNames'      => ['border-dashed'],
            ],
            default => [
                'backgroundColor' => '#E5E7EB',
                'borderColor'     => '#9CA3AF',
                'textColor'       => '#374151',
                'classNames'      => ['border-dashed', 'opacity-80'],
            ],
        };
    }

    protected function taskEvents(array $fetchInfo): array
    {
        return $this->visibleTasksQuery()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [Carbon::parse($fetchInfo['start'])->toDateString(), Carbon::parse($fetchInfo['end'])->toDateString()])
            ->get()
            ->map(fn (MeetingTask $task): array => [
                'id'              => 'task-'.$task->id,
                'title'           => __('meetings::meetings.calendar.task_title', ['title' => $task->title]),
                'start'           => $task->due_date?->toDateString(),
                'allDay'          => true,
                'backgroundColor' => $task->status === 'completed' ? '#6B7280' : '#F59E0B',
                'borderColor'     => $task->status === 'completed' ? '#6B7280' : '#F59E0B',
                'extendedProps'   => [
                    'eventType' => 'task',
                    'isPast'    => $task->due_date?->isPast(),
                ],
            ])
            ->all();
    }

    protected function timeOffEvents(array $fetchInfo): array
    {
        if (! class_exists(Leave::class) || ! \Schema::hasTable('time_off_leaves')) {
            return [];
        }

        return Leave::query()
            ->where('request_date_from', '<=', $fetchInfo['end'])
            ->where('request_date_to', '>=', $fetchInfo['start'])
            ->with('holidayStatus', 'user')
            ->get()
            ->map(fn (Leave $leave): array => [
                'id'              => 'time-off-'.$leave->id,
                'title'           => $leave->holidayStatus?->name.' '.$leave->user?->name,
                'start'           => $leave->request_date_from,
                'end'             => $leave->request_date_to ? Carbon::parse($leave->request_date_to)->addDay()->toDateString() : null,
                'allDay'          => true,
                'display'         => 'background',
                'backgroundColor' => $leave->holidayStatus?->color ?? '#94A3B8',
                'extendedProps'   => [
                    'eventType' => 'time_off',
                ],
            ])
            ->all();
    }

    protected function eventColor(string $type): string
    {
        return match ($type) {
            'internal'  => '#3B82F6',
            'external'  => '#8B5CF6',
            'emergency' => '#EF4444',
            'board'     => '#10B981',
            default     => '#6B7280',
        };
    }

    /**
     * @return array<int, array{label: string, background: string, border: string, dashed: bool}>
     */
    public function getStatusLegendItems(): array
    {
        return [
            [
                'label'      => __('meetings::meetings.calendar.status_legend.confirmed'),
                'background' => '#3B82F6',
                'border'     => '#3B82F6',
                'dashed'     => false,
            ],
            [
                'label'      => __('meetings::meetings.calendar.status_legend.approved'),
                'background' => '#BFDBFE',
                'border'     => '#3B82F6',
                'dashed'     => true,
            ],
            [
                'label'      => __('meetings::meetings.calendar.status_legend.pending_approval'),
                'background' => '#FDE68A',
                'border'     => '#F59E0B',
                'dashed'     => true,
            ],
            [
                'label'      => __('meetings::meetings.calendar.status_legend.draft'),
                'background' => '#E5E7EB',
                'border'     => '#9CA3AF',
                'dashed'     => true,
            ],
        ];
    }
}
