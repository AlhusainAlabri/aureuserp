<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Invoice\Models\Incoterm;

class IncotermPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('view_any_invoice_incoterm');
    }

    public function view(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('view_invoice_incoterm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice_incoterm');
    }

    public function update(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('update_invoice_incoterm');
    }

    public function delete(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('delete_invoice_incoterm');
    }

    public function deleteAny(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('delete_any_invoice_incoterm');
    }

    public function restore(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('restore_invoice_incoterm');
    }

    public function restoreAny(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('restore_any_invoice_incoterm');
    }

    public function forceDelete(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('force_delete_invoice_incoterm');
    }

    public function forceDeleteAny(AuthUser $authUser, Incoterm $incoterm): bool
    {
        return $authUser->can('force_delete_any_invoice_incoterm');
    }
}
