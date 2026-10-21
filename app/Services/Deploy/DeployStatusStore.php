<?php

namespace App\Services\Deploy;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DeployStatusStore
{
    private const int StartingTimeoutSeconds = 90;

    private const int RunningTimeoutSeconds = 2700;

    private const int MaxLogLength = 60000;

    /**
     * @return array{
     *     state: string,
     *     pid: int|null,
     *     user_id: int|null,
     *     started_at: string|null,
     *     finished_at: string|null,
     *     step: string|null,
     *     message: string|null,
     *     from_sha: string|null,
     *     to_sha: string|null,
     *     log: string
     * }
     */
    public function get(): array
    {
        $status = $this->read();

        if (! $this->isStale($status)) {
            return $status;
        }

        return $this->update(function (array $current) use ($status): array {
            if (($current['state'] ?? 'idle') !== ($status['state'] ?? 'idle')) {
                return $current;
            }

            $current['state'] = 'failed';
            $current['finished_at'] = now()->toIso8601String();
            $current['message'] = ($status['state'] ?? '') === 'starting'
                ? __('github-deploy.process_stopped')
                : __('github-deploy.timed_out');
            $current['pid'] = null;

            return $current;
        });
    }

    public function isActive(): bool
    {
        return in_array($this->get()['state'], ['starting', 'running'], true);
    }

    public function markStarting(int $userId): void
    {
        if ($this->isActive()) {
            throw new RuntimeException(__('github-deploy.already_running'));
        }

        $this->update(function (array $status) use ($userId): array {
            return [
                'state'       => 'starting',
                'pid'         => null,
                'user_id'     => $userId,
                'started_at'  => now()->toIso8601String(),
                'finished_at' => null,
                'step'        => 'starting',
                'message'     => null,
                'from_sha'    => null,
                'to_sha'      => null,
                'log'         => '',
            ];
        });
    }

    public function claim(?int $userId): void
    {
        $current = $this->read();
        $state = $current['state'] ?? 'idle';

        if ($state === 'running' && $this->pidIsAlive((int) ($current['pid'] ?? 0))) {
            throw new RuntimeException(__('github-deploy.already_running'));
        }

        $this->update(function (array $status) use ($userId): array {
            $status['state'] = 'running';
            $status['pid'] = getmypid() ?: null;
            $status['user_id'] = $userId ?? ($status['user_id'] ?? null);
            $status['started_at'] = $status['started_at'] ?? now()->toIso8601String();
            $status['finished_at'] = null;
            $status['step'] = 'prepare';
            $status['message'] = null;
            $status['log'] = '';

            return $status;
        });
    }

    public function step(string $step): void
    {
        $this->update(function (array $status) use ($step): array {
            $status['step'] = $step;

            return $status;
        });
    }

    public function rememberSha(?string $fromSha): void
    {
        $this->update(function (array $status) use ($fromSha): array {
            $status['from_sha'] = $fromSha;

            return $status;
        });
    }

    public function appendLog(string $step, string $output): void
    {
        $output = trim($output);

        if ($output === '') {
            return;
        }

        $this->update(function (array $status) use ($step, $output): array {
            $entry = '['.$step.']'."\n".$output;
            $log = trim(($status['log'] ?? '')."\n\n".$entry);
            $status['log'] = substr($log, -self::MaxLogLength);

            return $status;
        });
    }

    public function finish(string $state, ?string $message, ?string $fromSha, ?string $toSha): void
    {
        $this->update(function (array $status) use ($state, $message, $fromSha, $toSha): array {
            $status['state'] = $state;
            $status['pid'] = null;
            $status['finished_at'] = now()->toIso8601String();
            $status['message'] = $message;
            $status['from_sha'] = $fromSha ?? ($status['from_sha'] ?? null);
            $status['to_sha'] = $toSha;
            $status['step'] = null;

            return $status;
        });
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function isStale(array $status): bool
    {
        $state = $status['state'] ?? 'idle';

        if (! in_array($state, ['starting', 'running'], true)) {
            return false;
        }

        if ($state === 'running' && ! $this->pidIsAlive((int) ($status['pid'] ?? 0))) {
            return true;
        }

        $startedAt = $status['started_at'] ?? null;

        if (! is_string($startedAt) || $startedAt === '') {
            return true;
        }

        $age = Carbon::parse($startedAt)->diffInSeconds(now());
        $limit = $state === 'starting' ? self::StartingTimeoutSeconds : self::RunningTimeoutSeconds;

        return $age > $limit;
    }

    private function pidIsAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (! function_exists('posix_kill')) {
            return true;
        }

        if (posix_kill($pid, 0)) {
            return true;
        }

        $error = function_exists('posix_get_last_error') ? posix_get_last_error() : null;

        // EPERM means the process exists but this user cannot signal it.
        if ($error === null || $error === 1) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return $this->blank();
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $this->blank();
        }

        try {
            flock($handle, LOCK_SH);
            $contents = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $decoded = json_decode(is_string($contents) ? $contents : '', true);

        if (! is_array($decoded)) {
            return $this->blank();
        }

        return array_merge($this->blank(), $decoded);
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    private function update(callable $callback): array
    {
        $path = $this->path();
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException(__('github-deploy.status_unwritable'));
        }

        $status = $this->blank();

        try {
            flock($handle, LOCK_EX);
            $contents = stream_get_contents($handle);
            $decoded = json_decode(is_string($contents) ? $contents : '', true);
            $status = array_merge($this->blank(), is_array($decoded) ? $decoded : []);
            $status = $callback($status);
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return $status;
    }

    private function path(): string
    {
        return rtrim((string) config('github-deploy.directory'), '/').'/status.json';
    }

    /**
     * @return array{
     *     state: string,
     *     pid: int|null,
     *     user_id: int|null,
     *     started_at: string|null,
     *     finished_at: string|null,
     *     step: string|null,
     *     message: string|null,
     *     from_sha: string|null,
     *     to_sha: string|null,
     *     log: string
     * }
     */
    private function blank(): array
    {
        return [
            'state'       => 'idle',
            'pid'         => null,
            'user_id'     => null,
            'started_at'  => null,
            'finished_at' => null,
            'step'        => null,
            'message'     => null,
            'from_sha'    => null,
            'to_sha'      => null,
            'log'         => '',
        ];
    }
}
