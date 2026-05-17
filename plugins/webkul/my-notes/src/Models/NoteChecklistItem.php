<?php

namespace Webkul\MyNotes\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\MyNotes\Database\Factories\NoteChecklistItemFactory;

class NoteChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'content',
        'is_checked',
        'sort_order',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    protected static function newFactory(): NoteChecklistItemFactory
    {
        return NoteChecklistItemFactory::new();
    }
}
