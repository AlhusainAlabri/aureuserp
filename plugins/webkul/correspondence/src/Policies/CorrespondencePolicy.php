<?php

namespace Webkul\Correspondence\Policies;

use Webkul\Correspondence\Models\Correspondence;
use Webkul\Security\Models\User;

class CorrespondencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_correspondence_correspondence');
    }

    public function view(User $user, Correspondence $correspondence): bool
    {
        return $user->can('view_correspondence_correspondence');
    }

    public function create(User $user): bool
    {
        return $user->can('create_correspondence_correspondence');
    }

    public function update(User $user, Correspondence $correspondence): bool
    {
        return $correspondence->status === 'draft' && $user->can('update_correspondence_correspondence');
    }

    public function delete(User $user, Correspondence $correspondence): bool
    {
        return $user->can('delete_correspondence_correspondence');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_correspondence_correspondence');
    }

    public function archive(User $user, Correspondence $correspondence): bool
    {
        return in_array($correspondence->status, ['sent', 'received'], true)
            && $user->can('archive_correspondence_correspondence');
    }

    public function send(User $user, Correspondence $correspondence): bool
    {
        return $correspondence->isOutgoing()
            && $correspondence->status === 'approved'
            && $user->can('send_correspondence_correspondence');
    }

    public function exportPdf(User $user, Correspondence $correspondence): bool
    {
        return $user->can('export_pdf_correspondence_correspondence');
    }

    public function manageFollowers(User $user, Correspondence $correspondence): bool
    {
        return $user->can('manage_followers_correspondence_correspondence');
    }

    public function viewAllDepartments(User $user): bool
    {
        return $user->can('view_all_departments_correspondence_correspondence')
            || $user->hasAnyRole(['Admin', 'admin', 'manager', 'admin_manager']);
    }
}
