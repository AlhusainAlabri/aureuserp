<?php

namespace Webkul\Correspondence\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Security\Models\User;

trait HasCorrespondenceVisibility
{
    protected function canSeeAllCorrespondence(?User $user): bool
    {
        return (bool) ($user?->can('view_all_departments_correspondence_correspondence') || $user?->hasAnyRole(['Admin', 'admin', 'manager', 'admin_manager']));
    }

    protected function visibleCorrespondenceQuery(): Builder
    {
        $user = auth()->user();

        if ($this->canSeeAllCorrespondence($user)) {
            return Correspondence::query();
        }

        $departmentId = $user?->employee?->department_id;

        return Correspondence::query()
            ->where(function (Builder $query) use ($user, $departmentId): void {
                $query->where('creator_id', $user?->id)
                    ->orWhere('to_user_id', $user?->id)
                    ->when($departmentId, fn (Builder $query): Builder => $query
                        ->orWhere('from_department_id', $departmentId)
                        ->orWhere('to_department_id', $departmentId));
            });
    }

    protected function pendingApprovalsQuery(): Builder
    {
        return Correspondence::query()
            ->outgoing()
            ->whereHas('approvals.stepInstances', fn (Builder $query): Builder => $query
                ->where('status', 'waiting')
                ->whereJsonContains('assigned_approver_ids', auth()->id()));
    }
}
