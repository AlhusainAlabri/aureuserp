<?php

namespace App\Policies;

use App\Models\Operations\SystemBackup;
use Webkul\Security\Models\User as AuthUser;

class SystemBackupPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_system_backup');
    }

    public function view(AuthUser $authUser, SystemBackup $systemBackup): bool
    {
        return $authUser->can('view_system_backup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_system_backup');
    }

    public function download(AuthUser $authUser, SystemBackup $systemBackup): bool
    {
        return $authUser->can('download_system_backup');
    }

    public function delete(AuthUser $authUser, SystemBackup $systemBackup): bool
    {
        return $authUser->can('delete_system_backup');
    }

    public function restore(AuthUser $authUser, SystemBackup $systemBackup): bool
    {
        return $authUser->can('restore_system_backup');
    }
}
