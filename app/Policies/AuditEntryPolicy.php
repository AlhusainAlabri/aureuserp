<?php

namespace App\Policies;

use App\Models\Audit\AuditEntry;
use Webkul\Security\Models\User as AuthUser;

/**
 * The audit log is read-only: it can be listed and viewed, nothing else is ever allowed.
 */
class AuditEntryPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_filament_audit::log');
    }

    public function view(AuthUser $authUser, AuditEntry $auditEntry): bool
    {
        return $authUser->can('view_filament_audit::log');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, AuditEntry $auditEntry): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, AuditEntry $auditEntry): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, AuditEntry $auditEntry): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, AuditEntry $auditEntry): bool
    {
        return false;
    }
}
