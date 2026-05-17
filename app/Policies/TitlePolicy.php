<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Partner\Models\Title;

class TitlePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Title $title): bool
    {
        return $authUser->can('view_any_partner_title');
    }

    public function view(AuthUser $authUser, Title $title): bool
    {
        return $authUser->can('view_partner_title');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_partner_title');
    }

    public function update(AuthUser $authUser, Title $title): bool
    {
        return $authUser->can('update_partner_title');
    }

    public function delete(AuthUser $authUser, Title $title): bool
    {
        return $authUser->can('delete_partner_title');
    }

    public function deleteAny(AuthUser $authUser, Title $title): bool
    {
        return $authUser->can('delete_any_partner_title');
    }
}
