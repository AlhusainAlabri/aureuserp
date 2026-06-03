<?php

namespace Webkul\Correspondence\Filament\Widgets\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Services\CorrespondenceVisibilityService;
use Webkul\Security\Models\User;

trait HasCorrespondenceVisibility
{
    protected function getTableEmptyStateHeading(): ?string
    {
        return __('correspondence::correspondence.empty.no_records');
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __('correspondence::correspondence.empty.no_records_description');
    }

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

        return CorrespondenceVisibilityService::applyDepartmentScope(Correspondence::query(), $user);
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
