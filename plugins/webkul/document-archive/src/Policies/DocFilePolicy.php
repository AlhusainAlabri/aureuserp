<?php

namespace Webkul\DocumentArchive\Policies;

use Webkul\DocumentArchive\Models\DocFile;
use Webkul\Security\Models\User;

class DocFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_document_archive_doc::file');
    }

    public function view(User $user, DocFile $file): bool
    {
        return $user->can('view_document_archive_doc::file');
    }

    public function create(User $user): bool
    {
        return $user->can('create_document_archive_doc::file');
    }

    public function update(User $user, DocFile $file): bool
    {
        return $user->can('update_document_archive_doc::file');
    }

    public function delete(User $user, DocFile $file): bool
    {
        return $user->can('delete_document_archive_doc::file');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_document_archive_doc::file');
    }

    public function download(User $user, DocFile $file): bool
    {
        return $user->can('download_document_archive_doc::file');
    }

    public function share(User $user, DocFile $file): bool
    {
        return $user->can('share_document_archive_doc::file');
    }
}
