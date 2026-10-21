<?php

namespace App\Services\Deploy;

use Illuminate\Support\Str;
use RuntimeException;

class GitHubUpdateInspector
{
    public function __construct(
        private readonly CommandRunner $commands,
    ) {}

    /**
     * @param  list<string>  $arguments
     * @return list<string>
     */
    public function command(array $arguments): array
    {
        return ['git', '-c', 'safe.directory='.base_path(), ...$arguments];
    }

    /**
     * @return list<string>
     */
    public function fetchCommand(): array
    {
        $remote = $this->safeRemote();
        $branch = $this->safeBranch();

        if ($remote === null || $branch === null) {
            throw new RuntimeException(__('github-deploy.blockers.branch'));
        }

        return $this->command(['fetch', $remote, $branch, '--prune']);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     can_deploy: bool,
     *     is_repository: bool,
     *     remote: string|null,
     *     branch: string|null,
     *     current_branch: string|null,
     *     remote_url: string|null,
     *     head: string|null,
     *     short_sha: string|null,
     *     subject: string|null,
     *     dirty: bool,
     *     ahead: int,
     *     behind: int,
     *     remote_known: bool,
     *     remote_head: string|null,
     *     fetched: bool,
     *     commits: list<array{sha: string, subject: string, author: string, date: string}>,
     *     blockers: list<string>
     * }
     */
    public function inspect(bool $fetch = false): array
    {
        $report = $this->emptyReport();

        if (! config('github-deploy.enabled')) {
            $report['blockers'][] = __('github-deploy.blockers.disabled');

            return $this->finalize($report);
        }

        if ($report['remote'] === null) {
            $report['blockers'][] = __('github-deploy.blockers.remote');
        }

        if ($report['branch'] === null) {
            $report['blockers'][] = __('github-deploy.blockers.branch');
        }

        if ($report['remote'] === null || $report['branch'] === null) {
            return $this->finalize($report);
        }

        $inside = $this->commands->run($this->command(['rev-parse', '--is-inside-work-tree']), base_path(), 30);

        if (! $inside->successful || trim($inside->output) !== 'true') {
            $report['blockers'][] = __('github-deploy.blockers.not_repo');

            return $this->finalize($report);
        }

        $report['is_repository'] = true;
        $this->readLocal($report);

        if ($fetch) {
            $fetched = $this->commands->run($this->fetchCommand(), base_path(), 180);
            $report['fetched'] = $fetched->successful;

            if (! $fetched->successful) {
                $report['blockers'][] = __('github-deploy.blockers.fetch', [
                    'output' => Str::limit(trim($fetched->output), 300),
                ]);

                return $this->finalize($report);
            }
        }

        $this->readRemote($report);

        if ($fetch && $report['remote_known'] !== true) {
            $report['blockers'][] = __('github-deploy.blockers.remote_ref', [
                'ref' => $report['remote'].'/'.$report['branch'],
            ]);
        }

        return $this->finalize($report);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function readLocal(array &$report): void
    {
        $branch = $this->commands->run($this->command(['rev-parse', '--abbrev-ref', 'HEAD']), base_path(), 30);
        $head = $this->commands->run($this->command(['rev-parse', 'HEAD']), base_path(), 30);
        $subject = $this->commands->run($this->command(['log', '-1', '--format=%s']), base_path(), 30);
        $status = $this->commands->run($this->command(['status', '--porcelain']), base_path(), 30);
        $remoteUrl = $this->commands->run($this->command(['remote', 'get-url', (string) $report['remote']]), base_path(), 30);

        $report['current_branch'] = $branch->successful ? trim($branch->output) : null;
        $report['head'] = $head->successful ? trim($head->output) : null;
        $report['short_sha'] = is_string($report['head']) ? substr($report['head'], 0, 12) : null;
        $report['subject'] = $subject->successful ? trim($subject->output) : null;
        $report['dirty'] = $status->successful && trim($status->output) !== '';
        $report['remote_url'] = $remoteUrl->successful ? $this->sanitizeRemoteUrl(trim($remoteUrl->output)) : null;

        if (! $head->successful) {
            $report['blockers'][] = __('github-deploy.blockers.git', [
                'output' => Str::limit(trim($head->output), 300),
            ]);
        }

        if ($report['current_branch'] !== $report['branch']) {
            $report['blockers'][] = __('github-deploy.blockers.wrong_branch', [
                'current'  => $report['current_branch'] ?: '—',
                'expected' => $report['branch'],
            ]);
        }

        if ($report['dirty']) {
            $report['blockers'][] = __('github-deploy.blockers.dirty');
        }

        if (! $remoteUrl->successful) {
            $report['blockers'][] = __('github-deploy.blockers.remote_missing', [
                'remote' => $report['remote'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function readRemote(array &$report): void
    {
        $remote = (string) $report['remote'];
        $branch = (string) $report['branch'];
        $ref = 'refs/remotes/'.$remote.'/'.$branch;
        $remoteHead = $this->commands->run($this->command(['rev-parse', '--verify', $ref]), base_path(), 30);

        if (! $remoteHead->successful) {
            $report['remote_known'] = false;

            return;
        }

        $report['remote_known'] = true;
        $report['remote_head'] = trim($remoteHead->output);

        $behind = $this->commands->run(
            $this->command(['rev-list', '--count', 'HEAD..'.$remote.'/'.$branch]),
            base_path(),
            30,
        );
        $ahead = $this->commands->run(
            $this->command(['rev-list', '--count', $remote.'/'.$branch.'..HEAD']),
            base_path(),
            30,
        );

        $report['behind'] = $behind->successful ? (int) trim($behind->output) : 0;
        $report['ahead'] = $ahead->successful ? (int) trim($ahead->output) : 0;

        if ($report['ahead'] > 0) {
            $report['blockers'][] = __('github-deploy.blockers.ahead', [
                'count' => $report['ahead'],
            ]);
        }

        if ($report['behind'] < 1) {
            $report['commits'] = [];

            return;
        }

        $log = $this->commands->run(
            $this->command([
                'log',
                '--format=%H%x1f%s%x1f%an%x1f%aI',
                '-n',
                '15',
                'HEAD..'.$remote.'/'.$branch,
            ]),
            base_path(),
            30,
        );

        if ($log->successful) {
            $report['commits'] = $this->parseCommits($log->output);
        }
    }

    /**
     * @return list<array{sha: string, subject: string, author: string, date: string}>
     */
    private function parseCommits(string $output): array
    {
        $commits = [];

        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            if ($line === '') {
                continue;
            }

            $parts = explode("\x1f", $line);

            if (count($parts) < 4) {
                continue;
            }

            $commits[] = [
                'sha'     => substr($parts[0], 0, 12),
                'subject' => $parts[1],
                'author'  => $parts[2],
                'date'    => $parts[3],
            ];
        }

        return $commits;
    }

    private function sanitizeRemoteUrl(string $url): string
    {
        $sanitized = preg_replace('/:\/\/[^@\s]+@/', '://', $url);

        return is_string($sanitized) ? $sanitized : $url;
    }

    private function safeRemote(): ?string
    {
        $remote = trim((string) config('github-deploy.remote'));

        if (preg_match('/^[A-Za-z0-9._-]+$/', $remote) !== 1) {
            return null;
        }

        return $remote;
    }

    private function safeBranch(): ?string
    {
        $branch = trim((string) config('github-deploy.branch'));

        if ($branch === '' || str_starts_with($branch, '-') || str_contains($branch, '..')) {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9._\/-]+$/', $branch) !== 1) {
            return null;
        }

        return $branch;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(): array
    {
        return [
            'enabled'        => (bool) config('github-deploy.enabled'),
            'can_deploy'     => false,
            'is_repository'  => false,
            'remote'         => $this->safeRemote(),
            'branch'         => $this->safeBranch(),
            'current_branch' => null,
            'remote_url'     => null,
            'head'           => null,
            'short_sha'      => null,
            'subject'        => null,
            'dirty'          => false,
            'ahead'          => 0,
            'behind'         => 0,
            'remote_known'   => false,
            'remote_head'    => null,
            'fetched'        => false,
            'commits'        => [],
            'blockers'       => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function finalize(array $report): array
    {
        $report['can_deploy'] = $report['enabled']
            && $report['blockers'] === []
            && $report['behind'] > 0
            && $report['ahead'] === 0
            && $report['remote_known'] === true;

        return $report;
    }
}
