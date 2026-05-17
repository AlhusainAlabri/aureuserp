<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\PluginManager\Models\Plugin;

class PluginPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Plugin $plugin): bool
    {
        return $authUser->can('view_any_plugin_manager_plugin');
    }

    public function view(AuthUser $authUser, Plugin $plugin): bool
    {
        return $authUser->can('view_plugin_manager_plugin');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_plugin_manager_plugin');
    }

    public function update(AuthUser $authUser, Plugin $plugin): bool
    {
        return $authUser->can('update_plugin_manager_plugin');
    }

    public function delete(AuthUser $authUser, Plugin $plugin): bool
    {
        return $authUser->can('delete_plugin_manager_plugin');
    }

    public function deleteAny(AuthUser $authUser, Plugin $plugin): bool
    {
        return $authUser->can('delete_any_plugin_manager_plugin');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_plugin_manager_plugin');
    }
}
