<?php

namespace Webkul\DocumentArchive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class DocFileActivity extends Model
{
    protected $table = 'doc_file_activities';

    protected $fillable = [
        'file_id',
        'user_id',
        'action',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(DocFile::class, 'file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
