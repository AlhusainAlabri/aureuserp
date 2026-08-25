<?php

namespace App\Services\Operations\Checks;

use App\Enums\Operations\HealthStatus;

readonly class HealthCheckResult
{
    /**
     * @param  array<string, mixed>  $metrics
     */
    public function __construct(
        public string $key,
        public string $label,
        public HealthStatus $status,
        public string $summary,
        public array $metrics = [],
        public ?string $message = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key'     => $this->key,
            'label'   => $this->label,
            'status'  => $this->status->value,
            'summary' => $this->summary,
            'metrics' => $this->metrics,
            'message' => $this->message,
        ];
    }
}
