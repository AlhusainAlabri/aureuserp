<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DashboardShortcut;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardShortcutPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('view_any_filament_dashboard::shortcut');
    }

    public function view(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('view_filament_dashboard::shortcut');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_filament_dashboard::shortcut');
    }

    public function update(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('update_filament_dashboard::shortcut');
    }

    public function delete(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('delete_filament_dashboard::shortcut');
    }

    public function restore(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('restore_filament_dashboard::shortcut');
    }

    public function deleteAny(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('delete_any_filament_dashboard::shortcut');
    }

    public function forceDelete(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('force_delete_filament_dashboard::shortcut');
    }

    public function forceDeleteAny(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('force_delete_any_filament_dashboard::shortcut');
    }

    public function restoreAny(AuthUser $authUser, DashboardShortcut $dashboardShortcut): bool
    {
        return $authUser->can('restore_any_filament_dashboard::shortcut');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_filament_dashboard::shortcut');
    }

}