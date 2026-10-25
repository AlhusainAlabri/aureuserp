<?php

namespace App\Providers;

use App\Listeners\Audit\RecordSignIn;
use App\Models\Audit\AuditEntry;
use App\Policies\AuditEntryPolicy;
use App\Support\Accounting\PeriodLock;
use App\Support\Audit\AuditEntryGuard;
use App\Support\Audit\AuditRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        Gate::policy(AuditEntry::class, AuditEntryPolicy::class);

        Event::listen([Login::class, Failed::class], RecordSignIn::class);

        // Queue workers and the scheduler outlive the request that moves the lock date, so they must not keep a stale one.
        Event::listen([JobProcessing::class, ScheduledTaskStarting::class], fn () => PeriodLock::forget());
    }
}
