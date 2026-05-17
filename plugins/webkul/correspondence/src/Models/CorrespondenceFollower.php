<?php

namespace Webkul\Correspondence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Correspondence\Database\Factories\CorrespondenceFollowerFactory;
use Webkul\Security\Models\User;

class CorrespondenceFollower extends Model
{
    use HasFactory;

    protected $fillable = [
        'correspondence_id',
        'user_id',
    ];

    public function correspondence(): BelongsTo
    {
        return $this->belongsTo(Correspondence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): CorrespondenceFollowerFactory
    {
        return CorrespondenceFollowerFactory::new();
    }
}
