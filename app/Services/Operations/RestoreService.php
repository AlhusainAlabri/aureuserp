<?php

namespace App\Services\Operations;

use App\Enums\Operations\BackupType;
use App\Models\Operations\SystemBackup;
use App\Models\Operations\SystemOperationLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;
use ZipArchive;

class RestoreService
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {}

    public function restore(SystemBackup $backup, User $user): void
    {
        if (! $backup->existsOnDisk()) {
            throw new \RuntimeException(__('operations.backups.errors.missing_file'));
        }

        SystemOperationLog::record('backup.restore_started', $backup, $user);

        Artisan::call('down', ['--retry' => 60]);

        try {
            $this->backupService->create(BackupType::Database, automatic: true, user: $user);

            match ($backup->type) {
                BackupType::Database => $this->restoreDatabase($backup),
                BackupType::Files    => $this->restoreFiles($backup),
                BackupType::Full     => $this->restoreFull($backup),
            };

            Artisan::call('optimize:clear');

            SystemOperationLog::record('backup.restore_completed', $backup, $user);
        } catch (\Throwable $exception) {
            SystemOperationLog::record('backup.restore_failed', $backup, $user, [
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            Artisan::call('up');
        }
    }

    protected function restoreDatabase(SystemBackup $backup): void
    {
        $absolutePath = Storage::disk($backup->disk)->path($backup->path);
        $driver = (string) config('database.connections.'.config('database.default').'.driver');

        match ($driver) {
            'mysql'  => $this->restoreMysql($absolutePath),
            'sqlite' => $this->restoreSqlite($absolutePath),
            default  => throw new \RuntimeException(__('operations.backups.errors.unsupported_driver', ['driver' => $driver])),
        };
    }

    protected function restoreMysql(string $absolutePath): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $database = (string) ($connection['database'] ?? '');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $command = str_ends_with($absolutePath, '.gz')
            ? sprintf('gunzip -c %s | mysql -h %s -P %s -u %s %s', escapeshellarg($absolutePath), escapeshellarg((string) ($connection['host'] ?? '127.0.0.1')), escapeshellarg((string) ($connection['port'] ?? '3306')), escapeshellarg((string) ($connection['username'] ?? 'root')), escapeshellarg($database))
            : sprintf('mysql -h %s -P %s -u %s %s < %s', escapeshellarg((string) ($connection['host'] ?? '127.0.0.1')), escapeshellarg((string) ($connection['port'] ?? '3306')), escapeshellarg((string) ($connection['username'] ?? 'root')), escapeshellarg($database), escapeshellarg($absolutePath));

        $env = [];

        if (filled($connection['password'] ?? null)) {
            $env['MYSQL_PWD'] = (string) $connection['password'];
        }

        $result = Process::env($env)->run(['bash', '-lc', $command]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        if (! $result->successful()) {
            throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
        }
    }

    protected function restoreSqlite(string $absolutePath): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $databasePath = (string) ($connection['database'] ?? '');

        File::copy($absolutePath, $databasePath);
    }

    protected function restoreFiles(SystemBackup $backup): void
    {
        $absolutePath = Storage::disk($backup->disk)->path($backup->path);
        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            throw new \RuntimeException(__('operations.backups.errors.zip_failed'));
        }

        $extractPath = storage_path('app/private/_restore_'.uniqid('', true));
        File::ensureDirectoryExists($extractPath);
        $zip->extractTo($extractPath);
        $zip->close();

        File::copyDirectory($extractPath.'/private', storage_path('app/private'));
        File::deleteDirectory($extractPath);
    }

    protected function restoreFull(SystemBackup $backup): void
    {
        $absolutePath = Storage::disk($backup->disk)->path($backup->path);
        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            throw new \RuntimeException(__('operations.backups.errors.zip_failed'));
        }

        $extractPath = storage_path('app/backups/restore_'.uniqid('', true));
        File::ensureDirectoryExists($extractPath);
        $zip->extractTo($extractPath);
        $zip->close();

        foreach (glob($extractPath.'/*') ?: [] as $item) {
            if (str_contains(basename($item), '_db')) {
                $this->restoreMysqlOrSqliteFile($item);

                continue;
            }

            if (str_contains(basename($item), '_files') && str_ends_with($item, '.zip')) {
                $this->restoreFilesArchive($item);
            }
        }

        File::deleteDirectory($extractPath);
    }

    protected function restoreMysqlOrSqliteFile(string $absolutePath): void
    {
        $driver = (string) config('database.connections.'.config('database.default').'.driver');

        if ($driver === 'sqlite') {
            $this->restoreSqlite($absolutePath);

            return;
        }

        $this->restoreMysql($absolutePath);
    }

    protected function restoreFilesArchive(string $absolutePath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            throw new \RuntimeException(__('operations.backups.errors.zip_failed'));
        }

        $extractPath = storage_path('app/private/_restore_'.uniqid('', true));
        File::ensureDirectoryExists($extractPath);
        $zip->extractTo($extractPath);
        $zip->close();

        if (is_dir($extractPath.'/private')) {
            File::copyDirectory($extractPath.'/private', storage_path('app/private'));
        }

        File::deleteDirectory($extractPath);
    }
}
