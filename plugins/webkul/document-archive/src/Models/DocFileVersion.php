<?php

namespace Webkul\DocumentArchive\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\DocumentArchive\Database\Factories\DocFileVersionFactory;
use Webkul\Security\Models\User;

class DocFileVersion extends Model
{
    use HasFactory;

    protected $table = 'doc_file_versions';

    protected $fillable = [
        'file_id',
        'version_number',
        'file_path',
        'file_size',
        'original_filename',
        'change_note',
        'creator_id',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'file_size'      => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(DocFile::class, 'file_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected static function newFactory(): DocFileVersionFactory
    {
        return DocFileVersionFactory::new();
    }
}
