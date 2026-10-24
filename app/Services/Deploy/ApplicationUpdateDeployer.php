<?php

namespace App\Services\Deploy;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ApplicationUpdateDeployer
{
    private bool $maintenance = false;

    public function __construct(
        private readonly CommandRunner $commands,
        private readonly DeployStatusStore $status,
        private readonly GitHubUpdateInspector $git,
    ) {}

    public function deploy(?int $userId): void
    {
        if (app()->environment('testing') && ! config('github-deploy.allow_in_tests')) {
            throw new RuntimeException(__('github-deploy.blocked_during_tests'));
        }

        if (! config('github-deploy.enabled')) {
            throw new RuntimeException(__('github-deploy.blockers.disabled'));
        }

        $this->status->claim($userId);
        $fromSha = null;

        try {
            $report = $this->git->inspect(false);
            $fromSha = is_string($report['head'] ?? null) ? $report['head'] : null;
            $this->status->rememberSha($fromSha);
            $this->assertDeployable($report);

            $this->runStep('fetch', $this->git->fetchCommand(), 180);

            $report = $this->git->inspect(false);
            $this->assertDeployable($report);

            if (! ($report['remote_known'] ?? false)) {
                throw new RuntimeException(__('github-deploy.blockers.remote_ref', [
                    'ref' => ($report['remote'] ?? '').'/'.($report['branch'] ?? ''),
                ]));
            }

            if (($report['behind'] ?? 0) < 1) {
                $this->status->finish('success', __('github-deploy.up_to_date'), $fromSha, $fromSha);
                Log::info('github-deploy.up_to_date', ['sha' => $fromSha, 'user_id' => $userId]);

                return;
            }

            if (config('github-deploy.backup_before_deploy')) {
                $this->runStep('backup', $this->artisan(['system:backup-run', '--type=database', '--sync']), 900);
            }

            $down = ['down', '--retry=60'];
            $secret = $this->maintenanceSecret();

            if ($secret !== null) {
                $down[] = '--secret='.$secret;
            }

            $this->runStep('maintenance', $this->artisan($down), 60);
            $this->maintenance = true;

            $remote = (string) $report['remote'];
            $branch = (string) $report['branch'];
            $this->runStep('update', $this->git->command(['merge', '--ff-only', $remote.'/'.$branch]), 120);
            $this->runStep('composer', $this->composerInstall(), 900);
            $this->runStep('config', $this->artisan(['config:clear']), 60);
            $this->runStep('migrate', $this->artisan(['migrate', '--force']), 900);
            $this->runStep('extension-schema', $this->artisan(['erp:ensure-extension-schema']), 300);
            $this->runStep('permissions', $this->artisan(['permissions:sync-extensions']), 300);

            if (config('github-deploy.run_shield')) {
                $this->runStep('shield', $this->artisan(['shield:generate', '--all', '--panel=admin']), 300);
            }

            if (config('github-deploy.optimize')) {
                $this->runStep('optimize', $this->artisan(['optimize']), 180);
            }

            if (config('github-deploy.cache_filament') && app()->environment('production')) {
                $this->runStep('filament', $this->artisan(['filament:cache-components']), 180);
            }

            $this->runStep('queue', $this->artisan(['queue:restart']), 60);

            $updated = $this->git->inspect(false);
            $toSha = is_string($updated['head'] ?? null) ? $updated['head'] : null;

            $this->status->finish('success', __('github-deploy.completed'), $fromSha, $toSha);
            Log::info('github-deploy.completed', [
                'from'    => $fromSha,
                'to'      => $toSha,
                'user_id' => $userId,
            ]);
        } catch (Throwable $exception) {
            $this->status->appendLog('error', $exception->getMessage());
            $this->status->finish('failed', $exception->getMessage(), $fromSha, null);
            Log::error('github-deploy.failed', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            $this->bringOnline();
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function assertDeployable(array $report): void
    {
        $blockers = $report['blockers'] ?? [];

        if ($blockers !== []) {
            throw new RuntimeException(implode("\n", $blockers));
        }
    }

    /**
     * @param  list<string>  $command
     */
    private function runStep(string $name, array $command, int $timeout): void
    {
        $this->status->step($name);
        $result = $this->commands->run($command, base_path(), $timeout);
        $this->status->appendLog($name, $result->output);

        if (! $result->successful) {
            throw new RuntimeException(__('github-deploy.step_failed', [
                'step'   => __('github-deploy.steps.'.$name),
                'output' => Str::limit(trim($result->output), 500),
            ]));
        }
    }

    private function bringOnline(): void
    {
        if (! $this->maintenance) {
            return;
        }

        $result = $this->commands->run($this->artisan(['up']), base_path(), 60);
        $this->status->appendLog('online', $result->output);

        if (! $result->successful) {
            Log::error('github-deploy.maintenance_stuck', ['output' => $result->output]);
        }
    }

    /**
     * @param  list<string>  $arguments
     * @return list<string>
     */
    private function artisan(array $arguments): array
    {
        return [$this->phpBinary(), base_path('artisan'), ...$arguments, '--no-interaction'];
    }

    /**
     * @return list<string>
     */
    private function composerInstall(): array
    {
        return [
            (string) config('github-deploy.composer_binary'),
            'install',
            '--no-dev',
            '--optimize-autoloader',
            '--no-interaction',
            '--no-progress',
        ];
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

    private function maintenanceSecret(): ?string
    {
        $secret = config('github-deploy.maintenance_secret');

        if (! is_string($secret) || preg_match('/^[A-Za-z0-9_-]{8,80}$/', $secret) !== 1) {
            return null;
        }

        return $secret;
    }
}
