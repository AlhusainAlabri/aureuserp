<?php

namespace App\Support\Console;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

class DestructiveCommandGuard
{
    public function assertAllowed(?string $command, InputInterface $input): void
    {
        if ($command === null || $command === '') {
            return;
        }

        if ($this->isExplicitlyAllowed()) {
            return;
        }

        if ($this->isSafeTestEnvironment()) {
            return;
        }

        if ($this->isBlockedCommand($command)) {
            $this->deny($command, 'This command wipes or reseeds database tables.');
        }

        if ($this->isPluginUninstallCommand($command)) {
            $this->deny($command, 'Plugin uninstall drops database tables.');
        }

        if ($command === 'erp:install' && $this->shouldBlockErpInstall($input)) {
            $this->deny($command, 'ERP is already installed. Reinstall requires explicit opt-in (see auth.md).');
        }
    }

    public function handleCommandStarting(CommandStarting $event): void
    {
        $this->assertAllowed($event->command, $event->input);
    }

    protected function isExplicitlyAllowed(): bool
    {
        return (bool) config('console-safety.allow_destructive_commands', false);
    }

    protected function isSafeTestEnvironment(): bool
    {
        if (! app()->environment('testing')) {
            return false;
        }

        return $this->isTestDatabaseName($this->currentDatabaseName());
    }

    protected function isTestDatabaseName(string $database): bool
    {
        $connection = (string) config('database.default');
        $isSqliteMemory = $connection === 'sqlite' && in_array($database, [':memory:', ''], true);

        return str_ends_with($database, '_test') || $isSqliteMemory;
    }

    protected function currentDatabaseName(): string
    {
        $connection = (string) config('database.default');

        return (string) config("database.connections.{$connection}.database");
    }

    protected function isBlockedCommand(string $command): bool
    {
        return in_array($command, config('console-safety.blocked_commands', []), true);
    }

    protected function isPluginUninstallCommand(string $command): bool
    {
        return str_ends_with($command, ':uninstall');
    }

    protected function shouldBlockErpInstall(InputInterface $input): bool
    {
        if (! File::exists(storage_path('installed'))) {
            return false;
        }

        return $input->hasParameterOption('--force');
    }

    protected function deny(string $command, string $reason): void
    {
        $database = $this->currentDatabaseName();

        throw new RuntimeException(
            "Blocked destructive Artisan command [{$command}] on database [{$database}]. {$reason} "
            .'Set ALLOW_DESTRUCTIVE_ARTISAN_COMMANDS=true in .env only for intentional recovery, '
            .'or use nodhum_erp_test when running tests (see .env.testing.example).'
        );
    }
}
