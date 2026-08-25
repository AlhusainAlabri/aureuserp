<?php

namespace App\Services\Operations\Checks;

interface HealthCheckContract
{
    public function key(): string;

    public function label(): string;

    public function run(): HealthCheckResult;
}
