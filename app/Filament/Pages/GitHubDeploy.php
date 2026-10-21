<?php

namespace App\Filament\Pages;

use App\Services\Deploy\DeployStatusStore;
use App\Services\Deploy\GitHubDeployLauncher;
use App\Services\Deploy\GitHubUpdateInspector;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Throwable;
use Webkul\Security\Models\User;

class GitHubDeploy extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'github-deploy';

    protected string $view = 'filament.pages.github-deploy';

    /**
     * @var array<string, mixed>
     */
    public array $report = [];

    /**
     * @var array<string, mixed>
     */
    public array $deploy = [];

    public function mount(GitHubUpdateInspector $git, DeployStatusStore $status): void
    {
        abort_unless(static::canAccess(), 403);

        $this->report = $git->inspect(false);
        $this->deploy = $status->get();
    }

    public static function getNavigationGroup(): string
    {
        return __('operations.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('github-deploy.navigation');
    }

    public function getTitle(): string
    {
        return __('github-deploy.title');
    }

    public function getSubheading(): ?string
    {
        return __('github-deploy.subheading');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('deploy_github_updates')) {
            return true;
        }

        return static::userHasRole($user, 'super_admin') || static::userHasRole($user, 'Admin');
    }

    private static function userHasRole(User $user, string $role): bool
    {
        $roleExists = Role::query()
            ->where('name', $role)
            ->where('guard_name', 'web')
            ->exists();

        if (! $roleExists) {
            return false;
        }

        return $user->hasRole($role);
    }

    public function isDeploying(): bool
    {
        return in_array($this->deploy['state'] ?? 'idle', ['starting', 'running'], true);
    }

    public function refreshDeployState(GitHubUpdateInspector $git, DeployStatusStore $status): void
    {
        $previous = $this->deploy['state'] ?? 'idle';
        $this->deploy = $status->get();
        $current = $this->deploy['state'] ?? 'idle';

        if ($previous !== $current && ! in_array($current, ['starting', 'running'], true)) {
            $this->report = $git->inspect(false);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkUpdates')
                ->label(__('github-deploy.check'))
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => (bool) config('github-deploy.enabled') && ! $this->isDeploying())
                ->action(function (GitHubUpdateInspector $git): void {
                    $this->report = $git->inspect(true);

                    if ($this->report['blockers'] !== []) {
                        Notification::make()
                            ->title(__('github-deploy.unavailable'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(($this->report['behind'] ?? 0) > 0
                            ? __('github-deploy.updates_available_title')
                            : __('github-deploy.up_to_date'))
                        ->success()
                        ->send();
                }),
            Action::make('deploy')
                ->label(__('github-deploy.deploy'))
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('warning')
                ->visible(fn (): bool => (bool) ($this->report['can_deploy'] ?? false) && ! $this->isDeploying())
                ->requiresConfirmation()
                ->modalHeading(__('github-deploy.deploy_confirm_heading'))
                ->modalDescription(fn (): string => __('github-deploy.deploy_confirm', [
                    'count' => $this->report['behind'] ?? 0,
                ]))
                ->modalSubmitActionLabel(__('github-deploy.deploy'))
                ->action(function (GitHubDeployLauncher $launcher, DeployStatusStore $status): void {
                    try {
                        $launcher->start((int) auth()->id());
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('github-deploy.could_not_install'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->deploy = $status->get();

                    Notification::make()
                        ->title(__('github-deploy.queued'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
