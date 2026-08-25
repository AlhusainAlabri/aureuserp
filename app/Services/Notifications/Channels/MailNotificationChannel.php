<?php

namespace App\Services\Notifications\Channels;

use App\Services\Notifications\NotificationDefinition;
use Illuminate\Support\Facades\Mail;
use Webkul\Security\Models\User;

class MailNotificationChannel
{
    public function send(User $recipient, NotificationDefinition $definition): void
    {
        if ($definition->mailFactory === null || blank($recipient->email)) {
            return;
        }

        $mailable = ($definition->mailFactory)($recipient);

        if ($mailable === null) {
            return;
        }

        Mail::to($recipient)->queue($mailable);
    }
}
