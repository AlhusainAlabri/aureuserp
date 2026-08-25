<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'database_enabled',
        'email_enabled',
    ];

    protected $casts = [
        'database_enabled' => 'boolean',
        'email_enabled'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
