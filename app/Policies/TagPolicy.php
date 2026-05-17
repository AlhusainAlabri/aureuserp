<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Sale\Models\Tag;

class TagPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('view_any_sale_tag');
    }

    public function view(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('view_sale_tag');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sale_tag');
    }

    public function update(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('update_sale_tag');
    }

    public function delete(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('delete_sale_tag');
    }

    public function deleteAny(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('delete_any_sale_tag');
    }
}
