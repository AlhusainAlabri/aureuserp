<?php

namespace App\Jobs\Operations;

use App\Services\Operations\HealthCheckService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\Security\Models\User;

class RunHealthCheckJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $triggeredBy = 'scheduled',
        public ?int $userId = null,
    ) {}

    public function handle(HealthCheckService $healthChecks): void
    {
        $user = $this->userId ? User::query()->find($this->userId) : null;

        $healthChecks->run($this->triggeredBy, $user);
    }
}
