<?php

namespace Webkul\Attendance\Policies;

use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Attendance\Services\AttendanceScope;
use Webkul\Security\Models\User;

/**
 * Overtime requests are generated from attendance days and decided once; they are never deleted. Seeing or
 * deciding one needs the employee to be in the user's scope, and a decided request is read-only.
 */
class OvertimeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_attendance_overtime::request');
    }

    public function view(User $user, OvertimeRequest $request): bool
    {
        return $user->can('view_attendance_overtime::request')
            && AttendanceScope::includes($user, (int) $request->employee_id);
    }

    public function create(User $user): bool
    {
        return $user->can('create_attendance_overtime::request');
    }

    public function update(User $user, OvertimeRequest $request): bool
    {
        return $this->canDecide($user, $request);
    }

    public function approve(User $user, OvertimeRequest $request): bool
    {
        return $this->canDecide($user, $request);
    }

    public function reject(User $user, OvertimeRequest $request): bool
    {
        return $this->canDecide($user, $request);
    }

    protected function canDecide(User $user, OvertimeRequest $request): bool
    {
        return $request->status === OvertimeStatus::Pending
            && $user->can('update_attendance_overtime::request')
            && AttendanceScope::includes($user, (int) $request->employee_id);
    }
}
