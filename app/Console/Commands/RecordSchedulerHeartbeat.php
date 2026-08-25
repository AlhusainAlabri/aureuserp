<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecordSchedulerHeartbeat extends Command
{
    protected $signature = 'system:scheduler-heartbeat';

    protected $description = 'Record that the Laravel scheduler executed successfully';

    public function handle(): int
    {
        Cache::put(
            config('operations.cache.scheduler_heartbeat'),
            now(),
            now()->addDay(),
        );

        return self::SUCCESS;
    }
}
