<?php

namespace Webkul\Meetings\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Webkul\Meetings\Models\Meeting;

trait InteractsWithMeetingDashboardFilters
{
    protected function filteredMeetingsQuery(): Builder
    {
        return $this->applyMeetingDashboardFilters($this->visibleMeetingsQuery());
    }

    protected function filteredTasksQuery(): Builder
    {
        return $this->applyTaskDashboardFilters($this->visibleTasksQuery());
    }

    /**
     * @return array<string, mixed>
     */
    protected function meetingDashboardFilters(): array
    {
        if (! property_exists($this, 'pageFilters')) {
            return [];
        }

        return $this->pageFilters ?? [];
    }

    protected function applyMeetingDashboardFilters(Builder $query): Builder
    {
        if (! Schema::hasTable('meetings')) {
            return $query->whereRaw('1 = 0');
        }

        $filters = $this->meetingDashboardFilters();
        $startDate = $filters['startDate'] ?? null;
        $endDate = $filters['endDate'] ?? null;
        $status = $filters['status'] ?? 'all';

        return $query
            ->when(filled($startDate), fn (Builder $builder): Builder => $builder->whereDate('meeting_date', '>=', $startDate))
            ->when(filled($endDate), fn (Builder $builder): Builder => $builder->whereDate('meeting_date', '<=', $endDate))
            ->when(filled($status) && $status !== 'all', fn (Builder $builder): Builder => $builder->where('status', $status));
    }

    protected function applyTaskDashboardFilters(Builder $query): Builder
    {
        if (! Schema::hasTable('meeting_tasks')) {
            return $query->whereRaw('1 = 0');
        }

        $filters = $this->meetingDashboardFilters();
        $startDate = $filters['startDate'] ?? null;
        $endDate = $filters['endDate'] ?? null;

        return $query
            ->when(filled($startDate), fn (Builder $builder): Builder => $builder->whereDate('due_date', '>=', $startDate))
            ->when(filled($endDate), fn (Builder $builder): Builder => $builder->whereDate('due_date', '<=', $endDate));
    }

    protected function pendingApprovalsForUserQuery(): Builder
    {
        if (! Schema::hasTable('meetings') || ! Schema::hasTable('approvals')) {
            return Meeting::query()->whereRaw('1 = 0');
        }

        return Meeting::query()
            ->whereHas('approvals.stepInstances', fn (Builder $query): Builder => $query
                ->where('status', 'waiting')
                ->whereJsonContains('assigned_approver_ids', auth()->id()));
    }
}
