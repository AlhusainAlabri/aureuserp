<?php

namespace Tests\Support;

use App\Services\Deploy\CommandResult;
use App\Services\Deploy\CommandRunner;

class ScriptedCommandRunner implements CommandRunner
{
    /** @var array<string, CommandResult> */
    private array $responses = [];

    /** @var list<array{prefix: string, result: CommandResult}> */
    private array $prefixes = [];

    /** @var list<string> */
    public array $ran = [];

    /**
     * @param  list<string>  $command
     */
    public function always(array $command, CommandResult $result): void
    {
        $this->responses[$this->key($command)] = $result;
    }

    public function whenStartsWith(string $prefix, CommandResult $result): void
    {
        $this->prefixes[] = ['prefix' => $prefix, 'result' => $result];
    }

    public function run(array $command, ?string $cwd = null, ?int $timeout = 120): CommandResult
    {
        $key = $this->key($command);
        $this->ran[] = $key;

        if (isset($this->responses[$key])) {
            return $this->responses[$key];
        }

        foreach ($this->prefixes as $prefix) {
            if (str_starts_with($key, $prefix['prefix'])) {
                return $prefix['result'];
            }
        }

        return CommandResult::fail('unexpected: '.$key);
    }

    /**
     * @param  list<string>  $command
     */
    public function saw(array $command): bool
    {
        return in_array($this->key($command), $this->ran, true);
    }

    /**
     * @param  list<string>  $command
     */
    private function key(array $command): string
    {
        return implode("\n", $command);
    }
}
