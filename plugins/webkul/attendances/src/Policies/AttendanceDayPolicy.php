<?php

namespace Webkul\Attendance\Policies;

use Webkul\Attendance\Models\AttendanceDay;
use Webkul\Attendance\Services\AttendanceScope;
use Webkul\Security\Models\User;

/**
 * Attendance days are records: they can be corrected by hand but never deleted. Seeing or changing a
 * day also needs the employee to be in the user's scope (HR: everyone; others: self and team).
 */
class AttendanceDayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_attendance_attendance::day');
    }

    public function view(User $user, AttendanceDay $day): bool
    {
        return $user->can('view_attendance_attendance::day')
            && AttendanceScope::includes($user, (int) $day->employee_id);
    }

    public function create(User $user): bool
    {
        return $user->can('create_attendance_attendance::day');
    }

    public function update(User $user, AttendanceDay $day): bool
    {
        return $user->can('update_attendance_attendance::day')
            && AttendanceScope::includes($user, (int) $day->employee_id);
    }
}
