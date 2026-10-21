<?php

namespace App\Services\Deploy;

final readonly class CommandResult
{
    public function __construct(
        public bool $successful,
        public string $output,
        public int $exitCode,
    ) {}

    public static function ok(string $output = ''): self
    {
        return new self(true, $output, 0);
    }

    public static function fail(string $output, int $exitCode = 1): self
    {
        return new self(false, $output, $exitCode);
    }
}
