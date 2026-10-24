<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy backed by the Filament Shield permissions generated for a resource,
 * e.g. permission "payroll_employee::component" gives "view_any_payroll_employee::component".
 */
abstract class ShieldPolicy
{
    use HandlesAuthorization;

    /**
     * The Shield permission suffix of the resource this policy protects.
     */
    abstract protected function permission(): string;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'view_any');
    }

    public function view(Authenticatable $user, Model $record): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(Authenticatable $user, Model $record): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(Authenticatable $user, Model $record): bool
    {
        return $this->allows($user, 'delete');
    }

    public function deleteAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'delete_any');
    }

    public function forceDelete(Authenticatable $user, Model $record): bool
    {
        return $this->allows($user, 'force_delete');
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'force_delete_any');
    }

    public function restore(Authenticatable $user, Model $record): bool
    {
        return $this->allows($user, 'restore');
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'restore_any');
    }

    public function replicate(Authenticatable $user, Model $record): bool
    {
        return $this->allows($user, 'replicate');
    }

    public function reorder(Authenticatable $user): bool
    {
        return $this->allows($user, 'reorder');
    }

    protected function allows(Authenticatable $user, string $ability): bool
    {
        return method_exists($user, 'can') && $user->can($ability.'_'.$this->permission());
    }
}
