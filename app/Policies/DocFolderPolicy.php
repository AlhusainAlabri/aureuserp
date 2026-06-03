<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\DocumentArchive\Models\DocFolder;

class DocFolderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, DocFolder $docFolder): bool
    {
        return $authUser->can('view_any_document_archive_doc::folder');
    }

    public function view(AuthUser $authUser, DocFolder $docFolder): bool
    {
        return $authUser->can('view_document_archive_doc::folder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_document_archive_doc::folder');
    }

    public function update(AuthUser $authUser, DocFolder $docFolder): bool
    {
        return $authUser->can('update_document_archive_doc::folder');
    }

    public function delete(AuthUser $authUser, DocFolder $docFolder): bool
    {
        return $authUser->can('delete_document_archive_doc::folder');
    }

    public function deleteAny(AuthUser $authUser, DocFolder $docFolder): bool
    {
        return $authUser->can('delete_any_document_archive_doc::folder');
    }

    public function managePermissions(AuthUser $authUser, DocFolder $docFolder): bool
    {
        return $authUser->can('manage_permissions_document_archive_doc::folder');
    }
}
