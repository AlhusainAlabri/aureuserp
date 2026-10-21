<?php

namespace App\Services\Deploy;

interface CommandRunner
{
    /**
     * @param  list<string>  $command
     */
    public function run(array $command, ?string $cwd = null, ?int $timeout = 120): CommandResult;
}
