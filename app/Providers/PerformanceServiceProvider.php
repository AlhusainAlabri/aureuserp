<?php

namespace App\Providers;

use App\Support\Performance\SlowQueryRecorder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PerformanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SlowQueryRecorder::class);
    }

    public function boot(): void
    {
        if (! config('performance.slow_query_log.enabled', false)) {
            return;
        }

        Event::listen(QueryExecuted::class, function (QueryExecuted $event): void {
            $this->app->make(SlowQueryRecorder::class)->record($event);
        });
    }
}
