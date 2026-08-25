<?php

use App\Support\Console\DestructiveCommandGuard;
use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    config([
        'console-safety.allow_destructive_commands' => false,
        'console-safety.blocked_commands'           => [
            'migrate:fresh',
            'migrate:reset',
            'migrate:refresh',
            'db:wipe',
            'db:seed',
        ],
        'database.default'                    => 'mysql',
        'database.connections.mysql.database' => 'nodhum_erp',
    ]);
});

it('blocks migrate fresh on the development database', function (): void {
    $guard = app(DestructiveCommandGuard::class);

    expect(fn (): mixed => $guard->assertAllowed('migrate:fresh', new ArrayInput([])))
        ->toThrow(RuntimeException::class);
});

it('blocks db seed on the development database', function (): void {
    $guard = app(DestructiveCommandGuard::class);

    expect(fn (): mixed => $guard->assertAllowed('db:seed', new ArrayInput([])))
        ->toThrow(RuntimeException::class);
});

it('blocks plugin uninstall on the development database', function (): void {
    $guard = app(DestructiveCommandGuard::class);

    expect(fn (): mixed => $guard->assertAllowed('meetings:uninstall', new ArrayInput([])))
        ->toThrow(RuntimeException::class);
});

it('allows destructive commands when explicitly enabled', function (): void {
    config(['console-safety.allow_destructive_commands' => true]);

    $guard = app(DestructiveCommandGuard::class);

    $guard->assertAllowed('migrate:fresh', new ArrayInput([]));

    expect(true)->toBeTrue();
});

it('allows destructive commands in testing against a test database', function (): void {
    app()->detectEnvironment(fn (): string => 'testing');

    config([
        'database.connections.mysql.database' => 'nodhum_erp_test',
    ]);

    $guard = app(DestructiveCommandGuard::class);

    $guard->assertAllowed('migrate:fresh', new ArrayInput([]));

    expect(true)->toBeTrue();
});

it('blocks erp install force when the app is already installed', function (): void {
    $installedPath = storage_path('installed');
    $originalContents = file_exists($installedPath) ? file_get_contents($installedPath) : null;

    file_put_contents($installedPath, 'test marker');

    try {
        $guard = app(DestructiveCommandGuard::class);

        expect(fn (): mixed => $guard->assertAllowed('erp:install', new ArrayInput(['--force' => true])))
            ->toThrow(RuntimeException::class);
    } finally {
        if ($originalContents !== null) {
            file_put_contents($installedPath, $originalContents);
        } else {
            @unlink($installedPath);
        }
    }
});

it('registers the guard on command starting events', function (): void {
    expect(fn (): mixed => event(new CommandStarting(
        'migrate:fresh',
        new ArrayInput([]),
        new BufferedOutput,
    )))
        ->toThrow(RuntimeException::class);
});
