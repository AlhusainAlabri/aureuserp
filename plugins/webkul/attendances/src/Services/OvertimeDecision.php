<?php

namespace Webkul\Attendance\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Webkul\Attendance\Enums\OvertimeStatus;
use Webkul\Attendance\Exceptions\AttendanceException;
use Webkul\Attendance\Models\OvertimeRequest;
use Webkul\Security\Models\User;

/**
 * Approves or rejects a pending overtime request. Only an approved request counts in the monthly
 * summary (and so in payroll). The row is locked while it is decided so a request cannot be decided twice.
 */
class OvertimeDecision
{
    /**
     * @throws AuthorizationException when the user may not decide this request
     * @throws AttendanceException when it was already decided or belongs to the user
     */
    public function approve(OvertimeRequest $request, User $by, ?string $note = null): OvertimeRequest
    {
        return $this->decide($request, $by, OvertimeStatus::Approved, 'approve', $note);
    }

    /**
     * @throws AuthorizationException when the user may not decide this request
     * @throws AttendanceException when it was already decided or belongs to the user
     */
    public function reject(OvertimeRequest $request, User $by, ?string $note = null): OvertimeRequest
    {
        return $this->decide($request, $by, OvertimeStatus::Rejected, 'reject', $note);
    }

    protected function decide(OvertimeRequest $request, User $by, OvertimeStatus $status, string $ability, ?string $note): OvertimeRequest
    {
        Gate::forUser($by)->authorize($ability, $request);

        return DB::transaction(function () use ($request, $by, $status, $note): OvertimeRequest {
            $locked = OvertimeRequest::query()
                ->with('employee')
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== OvertimeStatus::Pending) {
                throw new AttendanceException(__('attendances::attendances.overtime.errors.already_decided'));
            }

            $employeeUserId = $locked->employee?->user_id;

            if ($employeeUserId !== null && (int) $employeeUserId === (int) $by->getKey()) {
                throw new AttendanceException(__('attendances::attendances.overtime.errors.own_request'));
            }

            $locked->forceFill([
                'status'     => $status,
                'decided_by' => $by->getKey(),
                'decided_at' => now(),
                'note'       => filled($note) ? $note : null,
            ])->save();

            $request->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }
}
