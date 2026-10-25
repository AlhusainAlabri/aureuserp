<?php

namespace App\Providers;

use App\Exceptions\PeriodLockedException;
use App\Http\Middleware\AtomicAccountingApiRequest;
use App\Listeners\Audit\RecordSignIn;
use App\Models\Audit\AuditEntry;
use App\Policies\AuditEntryPolicy;
use App\Support\Accounting\PeriodLock;
use App\Support\Accounting\PeriodLockGuard;
use App\Support\Audit\AuditEntryGuard;
use App\Support\Audit\AuditRecorder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Components\ComponentManager;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel as FoundationHttpKernel;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Throwable;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Accounting actions that write several records in a row (pay, reconcile, unreconcile, reset, cancel, confirm,
     * reverse). Filament wraps an action in a transaction only when it is enabled, so a refusal half-way through would
     * otherwise leave the earlier writes behind. Named as strings so the classes (some replaced from app/Overrides by a
     * prepended autoloader) are not loaded here.
     *
     * @var array<int, string>
     */
    public const ATOMIC_ACCOUNTING_ACTIONS = [
        'Webkul\\Account\\Filament\\Resources\\InvoiceResource\\Actions\\PayAction',
        'Webkul\\Account\\Filament\\Resources\\InvoiceResource\\Actions\\ResetToDraftAction',
        'Webkul\\Account\\Filament\\Resources\\InvoiceResource\\Actions\\CancelAction',
        'Webkul\\Account\\Filament\\Resources\\InvoiceResource\\Actions\\ConfirmAction',
        'Webkul\\Account\\Filament\\Resources\\InvoiceResource\\Actions\\ReverseAction',
        'Webkul\\Account\\Filament\\Resources\\PaymentResource\\Actions\\ResetToDraftAction',
        'Webkul\\Account\\Filament\\Resources\\PaymentResource\\Actions\\CancelAction',
        'Webkul\\Account\\Filament\\Resources\\PaymentResource\\Actions\\ConfirmAction',
    ];

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
        $this->makeAccountingWritesAtomic();
    }

    protected function makeAccountingWritesAtomic(): void
    {
        foreach (self::ATOMIC_ACCOUNTING_ACTIONS as $actionClass) {
            ComponentManager::resolve()->configureUsing($actionClass, fn (Action $action): Action => $action->databaseTransaction());
        }

        $kernel = $this->app->make(HttpKernel::class);

        if ($kernel instanceof FoundationHttpKernel && ! $kernel->hasMiddleware(AtomicAccountingApiRequest::class)) {
            $kernel->pushMiddleware(AtomicAccountingApiRequest::class);
        }
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
