<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\Correspondence\Models\Correspondence;

class CorrespondencePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('view_any_correspondence_correspondence');
    }

    public function view(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('view_correspondence_correspondence');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_correspondence_correspondence');
    }

    public function update(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('update_correspondence_correspondence');
    }

    public function delete(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('delete_correspondence_correspondence');
    }

    public function deleteAny(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('delete_any_correspondence_correspondence');
    }

    public function archive(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('archive_correspondence_correspondence');
    }

    public function send(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('send_correspondence_correspondence');
    }

    public function exportPdf(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('export_pdf_correspondence_correspondence');
    }

    public function manageFollowers(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('manage_followers_correspondence_correspondence');
    }

    public function viewAllDepartments(AuthUser $authUser, Correspondence $correspondence): bool
    {
        return $authUser->can('view_all_departments_correspondence_correspondence');
    }
}
