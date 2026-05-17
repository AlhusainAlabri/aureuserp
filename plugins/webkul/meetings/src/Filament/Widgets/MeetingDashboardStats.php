<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingTask;

class MeetingDashboardStats extends BaseWidget
{
    use HasMeetingVisibility;

    protected ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $meetings = $this->visibleMeetingsQuery();
        $tasks = $this->visibleTasksQuery();
        $pendingApprovals = $this->pendingApprovalsQuery()->count();
        $nextMeeting = $meetings->clone()
            ->whereBetween('meeting_date', [now(), now()->addDays(7)])
            ->orderBy('meeting_date')
            ->first();

        $stats = [
            Stat::make(__('meetings::meetings.dashboard.stats.total'), $meetings->clone()->count())
                ->description(__('meetings::meetings.dashboard.stats.this_month', [
                    'count' => $meetings->clone()->whereMonth('meeting_date', now()->month)->whereYear('meeting_date', now()->year)->count(),
                ]))
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make(__('meetings::meetings.dashboard.stats.pending_approval'), $meetings->clone()->pendingApproval()->count())
                ->description(__('meetings::meetings.dashboard.stats.pending_hint'))
                ->descriptionIcon('heroicon-o-clock')
                ->url(MeetingResource::getUrl('index', ['tableFilters[status][value]' => 'pending_approval']))
                ->color('warning'),
            Stat::make(__('meetings::meetings.dashboard.stats.overdue_tasks'), $tasks->clone()->overdue()->count())
                ->description(__('meetings::meetings.dashboard.stats.assigned_to_me', [
                    'count' => MeetingTask::query()->overdue()->where('assigned_to', auth()->id())->count(),
                ]))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($tasks->clone()->overdue()->exists() ? 'danger' : 'gray'),
            Stat::make(__('meetings::meetings.dashboard.stats.this_week'), $meetings->clone()->whereBetween('meeting_date', [now(), now()->addDays(7)])->count())
                ->description(__('meetings::meetings.dashboard.stats.next', [
                    'title' => str($nextMeeting?->title ?? '-')->limit(20),
                ]))
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('success'),
        ];

        if ($pendingApprovals > 0) {
            $stats[] = Stat::make(__('meetings::meetings.dashboard.stats.my_approvals'), $pendingApprovals)
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info');
        }

        return $stats;
    }

    protected function pendingApprovalsQuery(): Builder
    {
        return Meeting::query()
            ->whereHas('approvals.stepInstances', fn (Builder $query): Builder => $query
                ->where('status', 'waiting')
                ->whereJsonContains('assigned_approver_ids', auth()->id()));
    }
}
