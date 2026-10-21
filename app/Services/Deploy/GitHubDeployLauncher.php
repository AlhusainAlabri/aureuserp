<?php

namespace App\Services\Deploy;

use RuntimeException;

class GitHubDeployLauncher
{
    public function __construct(
        private readonly CommandRunner $commands,
        private readonly DeployStatusStore $status,
    ) {}

    public function start(int $userId): void
    {
        if (! function_exists('proc_open')) {
            throw new RuntimeException(__('github-deploy.exec_disabled'));
        }

        if (app()->environment('testing') && ! config('github-deploy.allow_in_tests')) {
            throw new RuntimeException(__('github-deploy.blocked_during_tests'));
        }

        $this->status->markStarting($userId);

        $php = $this->phpBinary();
        $directory = rtrim((string) config('github-deploy.directory'), '/');
        $logFile = $directory.'/launcher.log';

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->status->finish('failed', __('github-deploy.status_unwritable'), null, null);

            throw new RuntimeException(__('github-deploy.status_unwritable'));
        }

        $command = sprintf(
            'nohup %s %s github:deploy --yes --user-id=%d >> %s 2>&1 < /dev/null &',
            escapeshellarg($php),
            escapeshellarg(base_path('artisan')),
            $userId,
            escapeshellarg($logFile),
        );

        $result = $this->commands->run(['sh', '-c', $command], base_path(), 15);

        if (! $result->successful) {
            $this->status->finish('failed', $result->output !== '' ? $result->output : __('github-deploy.process_stopped'), null, null);

            throw new RuntimeException($result->output !== '' ? $result->output : __('github-deploy.process_stopped'));
        }
    }

    private function phpBinary(): string
    {
        $configured = config('github-deploy.php_binary');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $binary = PHP_BINARY;

        if (str_contains(strtolower($binary), 'fpm') || str_contains(strtolower($binary), 'cgi')) {
            return 'php';
        }

        return $binary;
    }
}
