<?php

declare(strict_types=1);

namespace App\Policies;

use App\Support\PluginManager\PluginManagerUi;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\PluginManager\Models\Plugin;

class PluginPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        if ($this->deniesWhenUiHidden()) {
            return false;
        }

        return $authUser->can('view_any_plugin_manager_plugin');
    }

    public function view(AuthUser $authUser, Plugin $plugin): bool
    {
        if ($this->deniesWhenUiHidden()) {
            return false;
        }

        return $authUser->can('view_plugin_manager_plugin');
    }

    public function create(AuthUser $authUser): bool
    {
        if ($this->deniesWhenUiHidden()) {
            return false;
        }

        return $authUser->can('create_plugin_manager_plugin');
    }

    public function update(AuthUser $authUser, Plugin $plugin): bool
    {
        if ($this->deniesWhenUiHidden()) {
            return false;
        }

        return $authUser->can('update_plugin_manager_plugin');
    }

    public function delete(AuthUser $authUser, Plugin $plugin): bool
    {
        if ($this->deniesWhenUiHidden()) {
            return false;
        }

        return $authUser->can('delete_plugin_manager_plugin');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        if ($this->deniesWhenUiHidden()) {
            return false;
        }

        return $authUser->can('delete_any_plugin_manager_plugin');
    }

    public function reorder(AuthUser $authUser): bool
    {
        if ($this->deniesWhenUiHidden()) {
            return false;
        }

        return $authUser->can('reorder_plugin_manager_plugin');
    }

    protected function deniesWhenUiHidden(): bool
    {
        return PluginManagerUi::blocksPluginPermission();
    }
}
