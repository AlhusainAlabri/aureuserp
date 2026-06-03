<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Webkul\DocumentArchive\Models\DocFile;

class DocFilePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser, DocFile $docFile): bool
    {
        return $authUser->can('view_any_document_archive_doc::file');
    }

    public function view(AuthUser $authUser, DocFile $docFile): bool
    {
        return $authUser->can('view_document_archive_doc::file');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_document_archive_doc::file');
    }

    public function update(AuthUser $authUser, DocFile $docFile): bool
    {
        return $authUser->can('update_document_archive_doc::file');
    }

    public function delete(AuthUser $authUser, DocFile $docFile): bool
    {
        return $authUser->can('delete_document_archive_doc::file');
    }

    public function deleteAny(AuthUser $authUser, DocFile $docFile): bool
    {
        return $authUser->can('delete_any_document_archive_doc::file');
    }

    public function download(AuthUser $authUser, DocFile $docFile): bool
    {
        return $authUser->can('download_document_archive_doc::file');
    }

    public function share(AuthUser $authUser, DocFile $docFile): bool
    {
        return $authUser->can('share_document_archive_doc::file');
    }
}
