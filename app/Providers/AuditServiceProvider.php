<?php

namespace App\Providers;

use App\Exceptions\PeriodLockedException;
use App\Listeners\Audit\RecordSignIn;
use App\Models\Audit\AuditEntry;
use App\Policies\AuditEntryPolicy;
use App\Support\Accounting\PeriodLock;
use App\Support\Accounting\PeriodLockGuard;
use App\Support\Audit\AuditEntryGuard;
use App\Support\Audit\AuditRecorder;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Throwable;

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
        PeriodLockGuard::register();

        Gate::policy(AuditEntry::class, AuditEntryPolicy::class);

        Event::listen([Login::class, Failed::class], RecordSignIn::class);

        // Queue workers and the scheduler outlive the request that moves the lock date, so they must not keep a stale one.
        Event::listen([JobProcessing::class, ScheduledTaskStarting::class], fn () => PeriodLock::forget());

        $this->showPeriodLockRefusalsAsNotifications();
    }

    /**
     * A refusal raised inside a Livewire call (a Filament form save or action) becomes a danger notification instead
     * of an error page. Filament has already rolled back the action's database transaction by the time it gets here.
     */
    protected function showPeriodLockRefusalsAsNotifications(): void
    {
        Livewire::listen('exception', function (mixed $component, Throwable $exception, callable $stopPropagation): void {
            if (! $exception instanceof PeriodLockedException) {
                return;
            }

            Notification::make()
                ->danger()
                ->title(__('audit.lock.refused_title'))
                ->body($exception->getMessage())
                ->send();

            $stopPropagation();
        });
    }
}
