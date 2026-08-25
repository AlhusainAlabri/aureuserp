<?php

namespace App\Console\Commands;

use App\Jobs\Operations\RunHealthCheckJob;
use App\Services\Operations\HealthCheckService;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Console\Command;

class RunSystemHealthCheck extends Command
{
    protected $signature = 'system:health-check {--sync : Run synchronously instead of queueing}';

    protected $description = 'Run system health checks and store the latest snapshot';

    public function handle(HealthCheckService $healthChecks): int
    {
        $settings = OperationsSettingsResolver::resolve();

        if (! $settings->monitoring_enabled) {
            $this->warn(__('operations.health.disabled'));

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $result = $healthChecks->run('manual');

            $this->info(__('operations.health.completed', [
                'status' => $result['overall_status']->getLabel(),
            ]));

            return self::SUCCESS;
        }

        RunHealthCheckJob::dispatch('scheduled');

        $this->info(__('operations.health.queued'));

        return self::SUCCESS;
    }
}
