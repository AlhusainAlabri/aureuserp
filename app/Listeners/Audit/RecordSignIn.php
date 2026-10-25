<?php

namespace App\Listeners\Audit;

use App\Support\Audit\AuditRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class RecordSignIn
{
    public function handle(Login|Failed $event): void
    {
        $user = $event->user;

        if (! $user instanceof Authenticatable || ! $user instanceof Model) {
            return;
        }

        AuditRecorder::record($user, $event instanceof Login ? 'login' : 'login_failed', [], $user);
    }
}
