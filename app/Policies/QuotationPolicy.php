<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\Quotation;

class QuotationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('view_any_sale_quotation');
    }

    public function view(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('view_sale_quotation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_quotation');
    }

    public function update(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('update_sale_quotation');
    }

    public function delete(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('delete_sale_quotation');
    }

    public function deleteAny(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('delete_any_sale_quotation');
    }

    public function restore(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('restore_sale_quotation');
    }

    public function restoreAny(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('restore_any_sale_quotation');
    }

    public function forceDelete(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('force_delete_sale_quotation');
    }

    public function forceDeleteAny(AuthUser $authUser, Quotation $quotation): bool
    {
        return $authUser->can('force_delete_any_sale_quotation');
    }
}
