<?php

namespace App\Console\Commands;

use App\Services\Deploy\GitHubUpdateInspector;
use Filament\Notifications\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Throwable;
use Webkul\Security\Models\User;

#[Signature('github:check-updates')]
#[Description('Check GitHub for commits that can be deployed and notify administrators')]
class CheckGitHubUpdates extends Command
{
    public function handle(GitHubUpdateInspector $git): int
    {
        if (! config('github-deploy.enabled')) {
            $this->info(__('github-deploy.disabled_cli'));

            return self::SUCCESS;
        }

        $report = $git->inspect(fetch: true);

        if ($report['blockers'] !== []) {
            $this->error(implode("\n", $report['blockers']));

            return self::FAILURE;
        }

        $behind = (int) $report['behind'];

        if ($behind < 1) {
            $this->info(__('github-deploy.up_to_date'));

            return self::SUCCESS;
        }

        $this->info(__('github-deploy.updates_cli', ['count' => $behind]));
        $this->notifyAdministrators($report, $behind);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function notifyAdministrators(array $report, int $behind): void
    {
        $remoteHead = $report['remote_head'] ?? null;

        if (! is_string($remoteHead) || $remoteHead === '') {
            return;
        }

        if (! Cache::add('github-deploy:notified:'.$remoteHead, true, now()->addDays(14))) {
            return;
        }

        if (! Schema::hasTable('notifications') || ! Schema::hasTable('roles')) {
            return;
        }

        try {
            $roles = Role::query()
                ->whereIn('name', ['super_admin', 'Admin'])
                ->where('guard_name', 'web')
                ->pluck('name');

            if ($roles->isEmpty()) {
                $this->warn(__('github-deploy.no_admins'));

                return;
            }

            $recipients = User::query()->role($roles->all())->get()->unique('id');
        } catch (Throwable $exception) {
            $this->warn($exception->getMessage());

            return;
        }

        if ($recipients->isEmpty()) {
            $this->warn(__('github-deploy.no_admins'));

            return;
        }

        Notification::make()
            ->title(__('github-deploy.updates_available_title'))
            ->body(__('github-deploy.updates_available_body', ['count' => $behind]))
            ->icon('heroicon-o-cloud-arrow-down')
            ->sendToDatabase($recipients);
    }
}
