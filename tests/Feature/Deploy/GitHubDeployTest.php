<?php

use App\Filament\Pages\GitHubDeploy;
use App\Services\Deploy\ApplicationUpdateDeployer;
use App\Services\Deploy\CommandResult;
use App\Services\Deploy\CommandRunner;
use App\Services\Deploy\DeployStatusStore;
use App\Services\Deploy\GitHubDeployLauncher;
use App\Services\Deploy\GitHubUpdateInspector;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\ScriptedCommandRunner;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    $directory = storage_path('framework/testing/github-deploy-'.str_replace('.', '', uniqid('', true)));
    File::ensureDirectoryExists($directory);

    config([
        'github-deploy.enabled'              => true,
        'github-deploy.allow_in_tests'       => false,
        'github-deploy.directory'            => $directory,
        'github-deploy.remote'               => 'origin',
        'github-deploy.branch'               => 'master',
        'github-deploy.backup_before_deploy' => false,
        'github-deploy.run_shield'           => false,
        'github-deploy.optimize'             => true,
        'github-deploy.cache_filament'       => false,
        'github-deploy.php_binary'           => 'php',
        'github-deploy.composer_binary'      => 'composer',
        'github-deploy.maintenance_secret'   => null,
    ]);

    app()->instance(CommandRunner::class, new ScriptedCommandRunner);
});

afterEach(function (): void {
    File::deleteDirectory((string) config('github-deploy.directory'));
});

function deployRunner(): ScriptedCommandRunner
{
    $runner = app(CommandRunner::class);

    expect($runner)->toBeInstanceOf(ScriptedCommandRunner::class);

    return $runner;
}

it('does not touch git while deploy is disabled', function (): void {
    config(['github-deploy.enabled' => false]);

    $report = app(GitHubUpdateInspector::class)->inspect(false);

    expect($report['blockers'])->toContain(__('github-deploy.blockers.disabled'))
        ->and($report['can_deploy'])->toBeFalse()
        ->and(deployRunner()->ran)->toBeEmpty();
});

it('hides credentials in the remote url and lists waiting commits', function (): void {
    scriptRepository(deployRunner(), behind: 1, remoteUrl: 'https://user:ghp_secret@github.com/org/repo.git');

    $report = app(GitHubUpdateInspector::class)->inspect(false);

    expect($report['remote_url'])->toBe('https://github.com/org/repo.git')
        ->and($report['remote_url'])->not->toContain('ghp_secret')
        ->and($report['behind'])->toBe(1)
        ->and($report['can_deploy'])->toBeTrue()
        ->and($report['commits'][0]['subject'])->toBe('Add update page');
});

it('blocks a dirty checkout before changing the server', function (): void {
    scriptRepository(deployRunner(), dirty: true, behind: 2);
    config(['github-deploy.allow_in_tests' => true]);

    expect(fn () => app(ApplicationUpdateDeployer::class)->deploy(1))
        ->toThrow(RuntimeException::class, __('github-deploy.blockers.dirty'));

    expect(deployRunner()->saw(artisanCommand(['down', '--retry=60'])))->toBeFalse();
    expect(app(DeployStatusStore::class)->get()['state'])->toBe('failed');
});

it('stops when the server already matches github', function (): void {
    scriptRepository(deployRunner(), behind: 0);
    config(['github-deploy.allow_in_tests' => true]);

    app(ApplicationUpdateDeployer::class)->deploy(4);

    $status = app(DeployStatusStore::class)->get();

    expect($status['state'])->toBe('success')
        ->and($status['message'])->toBe(__('github-deploy.up_to_date'))
        ->and(deployRunner()->saw(artisanCommand(['down', '--retry=60'])))->toBeFalse()
        ->and(deployRunner()->saw(artisanCommand(['up'])))->toBeFalse();
});

it('fast-forwards, migrates, and brings the site back when a step fails', function (): void {
    scriptRepository(deployRunner(), behind: 2);
    deployRunner()->always(
        ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress'],
        CommandResult::fail('composer exploded'),
    );
    config(['github-deploy.allow_in_tests' => true]);

    expect(fn () => app(ApplicationUpdateDeployer::class)->deploy(9))
        ->toThrow(RuntimeException::class);

    expect(deployRunner()->saw(artisanCommand(['down', '--retry=60'])))->toBeTrue()
        ->and(deployRunner()->saw(artisanCommand(['migrate', '--force'])))->toBeFalse()
        ->and(deployRunner()->saw(artisanCommand(['up'])))->toBeTrue()
        ->and(app(DeployStatusStore::class)->get()['state'])->toBe('failed');
});

it('runs the deploy steps in order for a fast-forward update', function (): void {
    scriptRepository(deployRunner(), behind: 2);
    config(['github-deploy.allow_in_tests' => true]);

    app(ApplicationUpdateDeployer::class)->deploy(3);

    $ran = deployRunner()->ran;
    $merge = array_search(implode("\n", gitCommand(['merge', '--ff-only', 'origin/master'])), $ran, true);
    $migrate = array_search(implode("\n", artisanCommand(['migrate', '--force'])), $ran, true);
    $up = array_search(implode("\n", artisanCommand(['up'])), $ran, true);

    expect($merge)->toBeInt()
        ->and($migrate)->toBeInt()
        ->and($up)->toBeInt()
        ->and($merge)->toBeLessThan($migrate)
        ->and($migrate)->toBeLessThan($up)
        ->and(app(DeployStatusStore::class)->get()['state'])->toBe('success');
});

it('refuses the artisan command without an explicit yes', function (): void {
    $this->artisan('github:deploy')->assertFailed();

    expect(deployRunner()->ran)->toBeEmpty();
});

it('refuses a real deploy while tests are running', function (): void {
    $this->artisan('github:deploy', ['--yes' => true])->assertFailed();

    expect(deployRunner()->ran)->toBeEmpty();
});

it('reports available updates once to administrators', function (): void {
    scriptRepository(deployRunner(), behind: 2);
    deployRunner()->always(gitCommand(['fetch', 'origin', 'master', '--prune']), CommandResult::ok());

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->artisan('github:check-updates')
        ->assertSuccessful()
        ->expectsOutputToContain(__('github-deploy.updates_cli', ['count' => 2]));

    expect(DatabaseNotification::query()->where('notifiable_id', $user->id)->count())->toBe(1);

    $this->artisan('github:check-updates')->assertSuccessful();

    expect(DatabaseNotification::query()->where('notifiable_id', $user->id)->count())->toBe(1);
});

it('hides the update page from users who are not administrators', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(GitHubDeploy::canAccess())->toBeFalse();

    Livewire::test(GitHubDeploy::class)->assertForbidden();
});

it('shows a plain message while deploy is turned off', function (): void {
    config(['github-deploy.enabled' => false]);

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(GitHubDeploy::class)
        ->assertSee(__('github-deploy.disabled_body'))
        ->assertDontSee('GITHUB_DEPLOY_');
});

it('starts a background deploy from the admin page', function (): void {
    scriptRepository(deployRunner(), behind: 1);
    deployRunner()->whenStartsWith("sh\n-c\n", CommandResult::ok());
    config(['github-deploy.allow_in_tests' => true]);

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(GitHubDeploy::class)
        ->callAction(TestAction::make('deploy'))
        ->assertNotified();

    expect(app(DeployStatusStore::class)->get()['state'])->toBe('starting')
        ->and(collect(deployRunner()->ran)->contains(fn (string $command): bool => str_starts_with($command, "sh\n-c\n")))->toBeTrue();
});

function scriptRepository(
    ScriptedCommandRunner $runner,
    int $behind = 0,
    int $ahead = 0,
    bool $dirty = false,
    string $remoteUrl = 'https://github.com/org/repo.git',
): void {
    $head = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    $remote = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    $runner->always(gitCommand(['rev-parse', '--is-inside-work-tree']), CommandResult::ok("true\n"));
    $runner->always(gitCommand(['rev-parse', '--abbrev-ref', 'HEAD']), CommandResult::ok("master\n"));
    $runner->always(gitCommand(['rev-parse', 'HEAD']), CommandResult::ok($head."\n"));
    $runner->always(gitCommand(['log', '-1', '--format=%s']), CommandResult::ok("Current commit\n"));
    $runner->always(gitCommand(['status', '--porcelain']), CommandResult::ok($dirty ? " M app/Example.php\n" : ''));
    $runner->always(gitCommand(['remote', 'get-url', 'origin']), CommandResult::ok($remoteUrl."\n"));
    $runner->always(gitCommand(['rev-parse', '--verify', 'refs/remotes/origin/master']), CommandResult::ok($remote."\n"));
    $runner->always(gitCommand(['rev-list', '--count', 'HEAD..origin/master']), CommandResult::ok($behind."\n"));
    $runner->always(gitCommand(['rev-list', '--count', 'origin/master..HEAD']), CommandResult::ok($ahead."\n"));
    $runner->always(gitCommand(['fetch', 'origin', 'master', '--prune']), CommandResult::ok());
    $runner->always(gitCommand(['merge', '--ff-only', 'origin/master']), CommandResult::ok());
    $runner->always(
        gitCommand(['log', '--format=%H%x1f%s%x1f%an%x1f%aI', '-n', '15', 'HEAD..origin/master']),
        CommandResult::ok("bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb\x1fAdd update page\x1fAda\x1f2026-10-05T12:00:00+04:00\n"),
    );

    foreach ([
        ['down', '--retry=60'],
        ['config:clear'],
        ['migrate', '--force'],
        ['permissions:sync-extensions'],
        ['optimize'],
        ['queue:restart'],
        ['up'],
    ] as $arguments) {
        $runner->always(artisanCommand($arguments), CommandResult::ok($arguments[0]));
    }

    $runner->always(
        ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress'],
        CommandResult::ok('composer ok'),
    );
}

/**
 * @param  list<string>  $arguments
 * @return list<string>
 */
function gitCommand(array $arguments): array
{
    return ['git', '-c', 'safe.directory='.base_path(), ...$arguments];
}

/**
 * @param  list<string>  $arguments
 * @return list<string>
 */
function artisanCommand(array $arguments): array
{
    return ['php', base_path('artisan'), ...$arguments, '--no-interaction'];
}

it('starts the launcher without leaving a failed status', function (): void {
    config(['github-deploy.allow_in_tests' => true]);
    deployRunner()->whenStartsWith("sh\n-c\n", CommandResult::ok());

    app(GitHubDeployLauncher::class)->start(12);

    expect(app(DeployStatusStore::class)->get()['state'])->toBe('starting')
        ->and(app(DeployStatusStore::class)->get()['user_id'])->toBe(12);
});
