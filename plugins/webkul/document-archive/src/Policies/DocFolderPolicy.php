<?php

namespace Webkul\DocumentArchive\Policies;

use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\Security\Models\User;

class DocFolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_document_archive_doc::folder');
    }

    public function view(User $user, DocFolder $folder): bool
    {
        return $user->can('view_document_archive_doc::folder');
    }

    public function create(User $user): bool
    {
        return $user->can('create_document_archive_doc::folder');
    }

    public function update(User $user, DocFolder $folder): bool
    {
        return $user->can('update_document_archive_doc::folder');
    }

    public function delete(User $user, DocFolder $folder): bool
    {
        return $user->can('delete_document_archive_doc::folder');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_document_archive_doc::folder');
    }

    public function managePermissions(User $user, DocFolder $folder): bool
    {
        return $user->can('manage_permissions_document_archive_doc::folder');
    }
}
