<?php

namespace App\Providers;

use App\Listeners\Approvals\SendApprovalEmailNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Wezlo\FilamentApproval\Events\ApprovalCompleted;
use Wezlo\FilamentApproval\Events\ApprovalEscalated;
use Wezlo\FilamentApproval\Events\ApprovalRejected;
use Wezlo\FilamentApproval\Events\ApprovalStepCompleted;
use Wezlo\FilamentApproval\Events\ApprovalSubmitted;

class ApprovalNotificationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! class_exists(ApprovalSubmitted::class)) {
            return;
        }

        $listener = SendApprovalEmailNotifications::class;

        Event::listen(ApprovalSubmitted::class, [$listener, 'handleSubmitted']);
        Event::listen(ApprovalStepCompleted::class, [$listener, 'handleStepCompleted']);
        Event::listen(ApprovalRejected::class, [$listener, 'handleRejected']);
        Event::listen(ApprovalCompleted::class, [$listener, 'handleCompleted']);
        Event::listen(ApprovalEscalated::class, [$listener, 'handleEscalated']);
    }
}
