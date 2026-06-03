<?php

namespace App\Traits\Hr;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Security\Models\User;

trait HasFileClosure
{
    public function isClosed(): bool
    {
        return (bool) ($this->is_closed ?? false);
    }

    public function canBeClosedBy(User $user): bool
    {
        return $user->can('close_employee_file');
    }

    public function close(User $user, string $reason, ?string $notes = null): void
    {
        if (! $this->canBeClosedBy($user)) {
            throw new RuntimeException(__('hr-extensions::employee.exceptions.cannot_close'));
        }

        DB::transaction(function () use ($user, $reason, $notes): void {
            $this->update([
                'is_closed'       => true,
                'closure_reason'  => $reason,
                'closure_notes'   => $notes,
                'closed_at'       => now(),
                'closed_by'       => $user->id,
                'reopen_reason'   => null,
                'reopened_at'     => null,
                'reopened_by'     => null,
                'is_active'       => false,
            ]);

            if ($this->user) {
                $this->user->update(['is_active' => false]);
            }
        });
    }

    public function reopen(User $user, string $reason): void
    {
        if (! $user->can('reopen_employee_file') && ! $user->hasRole('hr_manager')) {
            throw new RuntimeException(__('hr-extensions::employee.exceptions.cannot_reopen'));
        }

        DB::transaction(function () use ($user, $reason): void {
            $this->update([
                'is_closed'      => false,
                'closure_reason' => null,
                'closure_notes'  => null,
                'closed_at'      => null,
                'closed_by'      => null,
                'reopen_reason'  => $reason,
                'reopened_at'    => now(),
                'reopened_by'    => $user->id,
                'is_active'      => true,
            ]);

            if ($this->user) {
                $this->user->update(['is_active' => true]);
            }
        });
    }
}
