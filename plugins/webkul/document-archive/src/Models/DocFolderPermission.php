<?php

namespace Webkul\DocumentArchive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class DocFolderPermission extends Model
{
    protected $table = 'doc_folder_permissions';

    protected $fillable = [
        'folder_id',
        'type',
        'user_id',
        'role_name',
        'permission',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocFolder::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
