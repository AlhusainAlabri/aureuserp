<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

class EmailLog extends Model
{
    protected $fillable = [
        'email_template_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'variables',
        'sender_user_id',
        'mail_class',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'sent_at'   => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->template();
    }
}
