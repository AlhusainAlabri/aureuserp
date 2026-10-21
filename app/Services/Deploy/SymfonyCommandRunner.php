<?php

namespace App\Services\Deploy;

use Symfony\Component\Process\Process;
use Throwable;

class SymfonyCommandRunner implements CommandRunner
{
    public function run(array $command, ?string $cwd = null, ?int $timeout = 120): CommandResult
    {
        $process = new Process($command, $cwd ?? base_path());
        $process->setTimeout($timeout);
        $process->setEnv($this->environment());

        try {
            $process->run();
        } catch (Throwable $exception) {
            return CommandResult::fail($exception->getMessage(), 1);
        }

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return new CommandResult(
            successful: $process->isSuccessful(),
            output: $output,
            exitCode: $process->getExitCode() ?? 1,
        );
    }

    /**
     * @return array<string, string>
     */
    private function environment(): array
    {
        $environment = [];

        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $environment[$key] = $value;
            }
        }

        $path = getenv('PATH');

        if (is_string($path) && $path !== '') {
            $environment['PATH'] = $path;
        }

        $environment['GIT_TERMINAL_PROMPT'] = '0';
        $environment['GIT_ASKPASS'] = 'echo';

        return $environment;
    }
}
