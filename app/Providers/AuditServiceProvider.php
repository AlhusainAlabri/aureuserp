<?php

namespace App\Providers;

use App\Listeners\Audit\RecordSignIn;
use App\Support\Audit\AuditEntryGuard;
use App\Support\Audit\AuditRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Registering model listeners does not read the database, and the recorder checks that the Chatter table exists
     * before it writes, so booting is safe before the plugins are installed.
     */
    public function boot(): void
    {
        AuditRecorder::register();
        AuditEntryGuard::register();

        Event::listen([Login::class, Failed::class], RecordSignIn::class);
    }
}
