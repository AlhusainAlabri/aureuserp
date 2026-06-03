<?php

namespace Webkul\Meetings\Filament\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Meetings\Filament\Resources\MeetingResource;
use Webkul\Meetings\Filament\Widgets\Concerns\HasMeetingVisibility;
use Webkul\Meetings\Filament\Widgets\Concerns\InteractsWithMeetingDashboardFilters;
use Webkul\Meetings\Models\Meeting;
use Webkul\Meetings\Models\MeetingTask;

class MeetingDashboardStats extends BaseWidget
{
    use HasMeetingVisibility;
    use InteractsWithMeetingDashboardFilters;
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'lg'      => 4,
        ];
    }

    protected function getStats(): array
    {
        $meetings = $this->filteredMeetingsQuery();
        $tasks = $this->filteredTasksQuery();
        $pendingApprovals = $this->pendingApprovalsQuery()->count();
        $nextMeeting = $meetings->clone()
            ->whereBetween('meeting_date', [now(), now()->addDays(7)])
            ->orderBy('meeting_date')
            ->first();

        $totalTrend = $this->monthlyComparison($meetings->clone());
        $pendingTrend = $this->monthlyComparison($meetings->clone()->pendingApproval());

        $stats = [
            $this->clickableStat(
                label: __('meetings::meetings.dashboard.stats.total'),
                value: $meetings->clone()->count(),
                description: __('meetings::meetings.dashboard.stats.this_month', [
                    'count' => $meetings->clone()->whereMonth('meeting_date', now()->month)->whereYear('meeting_date', now()->year)->count(),
                ]),
                descriptionIcon: $totalTrend['icon'],
                color: 'primary',
                url: MeetingResource::getUrl('index'),
                chart: $this->monthlySparkline($meetings->clone()),
                extraDescription: $totalTrend['description'],
            ),
            $this->clickableStat(
                label: __('meetings::meetings.dashboard.stats.pending_approval'),
                value: $meetings->clone()->pendingApproval()->count(),
                description: __('meetings::meetings.dashboard.stats.pending_hint'),
                descriptionIcon: $pendingTrend['icon'],
                color: 'warning',
                url: MeetingResource::getUrl('index', ['activeTab' => 'pending_approval']),
                chart: $this->monthlySparkline($meetings->clone()->pendingApproval()),
                extraDescription: $pendingTrend['description'],
            ),
            $this->clickableStat(
                label: __('meetings::meetings.dashboard.stats.overdue_tasks'),
                value: $tasks->clone()->overdue()->count(),
                description: __('meetings::meetings.dashboard.stats.assigned_to_me', [
                    'count' => MeetingTask::query()->overdue()->where('assigned_to', auth()->id())->count(),
                ]),
                descriptionIcon: 'heroicon-m-exclamation-triangle',
                color: $tasks->clone()->overdue()->exists() ? 'danger' : 'gray',
                url: MeetingResource::getUrl('index'),
                chart: $this->taskSparkline($tasks->clone()->overdue()),
            ),
            $this->clickableStat(
                label: __('meetings::meetings.dashboard.stats.this_week'),
                value: $meetings->clone()->whereBetween('meeting_date', [now(), now()->addDays(7)])->count(),
                description: __('meetings::meetings.dashboard.stats.next', [
                    'title' => str($nextMeeting?->title ?? '-')->limit(20),
                ]),
                descriptionIcon: 'heroicon-m-calendar-days',
                color: 'success',
                url: MeetingResource::getUrl('index', ['activeTab' => 'confirmed']),
                chart: $this->monthlySparkline(
                    $meetings->clone()->whereBetween('meeting_date', [now()->subMonths(5)->startOfMonth(), now()->endOfMonth()])
                ),
            ),
        ];

        if ($pendingApprovals > 0) {
            $stats[] = $this->clickableStat(
                label: __('meetings::meetings.dashboard.stats.my_approvals'),
                value: $pendingApprovals,
                description: __('meetings::meetings.dashboard.stats.pending_hint'),
                descriptionIcon: 'heroicon-m-check-circle',
                color: 'info',
                url: MeetingResource::getUrl('index', ['activeTab' => 'pending_approval']),
                chart: $this->monthlySparkline($this->applyMeetingDashboardFilters($this->pendingApprovalsQuery())),
            );
        }

        return $stats;
    }

    protected function clickableStat(
        string $label,
        int|string $value,
        string $description,
        string $descriptionIcon,
        string $color,
        string $url,
        array $chart,
        ?string $extraDescription = null,
    ): Stat {
        $stat = Stat::make($label, $value)
            ->description(trim($description.' '.($extraDescription ?? '')))
            ->descriptionIcon($descriptionIcon, IconPosition::Before)
            ->color($color)
            ->url($url)
            ->chart($chart)
            ->extraAttributes(['class' => 'cursor-pointer']);

        return $stat;
    }

    /**
     * @return array{description: string, icon: string}
     */
    protected function monthlyComparison(Builder $query): array
    {
        $current = (clone $query)
            ->whereMonth('meeting_date', now()->month)
            ->whereYear('meeting_date', now()->year)
            ->count();
        $previous = (clone $query)
            ->whereMonth('meeting_date', now()->subMonth()->month)
            ->whereYear('meeting_date', now()->subMonth()->year)
            ->count();

        if ($previous === 0) {
            return [
                'description' => __('meetings::meetings.dashboard.stats.vs_last_month', ['change' => '—']),
                'icon'        => 'heroicon-m-minus',
            ];
        }

        $change = round((($current - $previous) / $previous) * 100, 1);
        $increase = $change >= 0;

        return [
            'description' => __('meetings::meetings.dashboard.stats.vs_last_month', [
                'change' => ($increase ? '+' : '').$change.'%',
            ]),
            'icon' => $increase ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function monthlySparkline(Builder $query): array
    {
        return Trend::query($query)
            ->between(start: now()->subMonths(5)->startOfMonth(), end: now()->endOfMonth())
            ->perMonth()
            ->count()
            ->map(fn (TrendValue $value): int => (int) $value->aggregate)
            ->toArray();
    }

    /**
     * @return array<int, int>
     */
    protected function taskSparkline(Builder $query): array
    {
        return Trend::query($query)
            ->between(start: now()->subMonths(5)->startOfMonth(), end: now()->endOfMonth())
            ->perMonth()
            ->count()
            ->map(fn (TrendValue $value): int => (int) $value->aggregate)
            ->toArray();
    }

    protected function pendingApprovalsQuery(): Builder
    {
        return Meeting::query()
            ->whereHas('approvals.stepInstances', fn (Builder $query): Builder => $query
                ->where('status', 'waiting')
                ->whereJsonContains('assigned_approver_ids', auth()->id()));
    }
}
