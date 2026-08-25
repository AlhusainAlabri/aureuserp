<?php

namespace App\Services\Operations;

use App\Enums\Operations\BackupStatus;
use App\Enums\Operations\BackupType;
use App\Models\Operations\SystemBackup;
use App\Models\Operations\SystemOperationLog;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;
use ZipArchive;

class BackupService
{
    public function create(BackupType $type, bool $automatic = false, ?User $user = null): SystemBackup
    {
        $settings = OperationsSettingsResolver::resolve();

        $timestamp = now()->format('Y-m-d_His');
        $name = "{$type->value}_{$timestamp}";

        $backup = SystemBackup::query()->create([
            'name'         => $name,
            'type'         => $type,
            'status'       => BackupStatus::Pending,
            'disk'         => $settings->backup_disk,
            'path'         => '',
            'is_automatic' => $automatic,
            'created_by'   => $user?->getKey(),
            'started_at'   => now(),
        ]);

        SystemOperationLog::record('backup.started', $backup, $user, [
            'type' => $type->value,
        ]);

        try {
            $backup->update(['status' => BackupStatus::Running]);

            $path = match ($type) {
                BackupType::Database => $this->backupDatabase($name, $settings->backup_disk),
                BackupType::Files    => $this->backupFiles($name, $settings->backup_disk),
                BackupType::Full     => $this->backupFull($name, $settings->backup_disk),
            };

            $disk = Storage::disk($settings->backup_disk);
            $size = $disk->exists($path) ? $disk->size($path) : 0;
            $checksum = $disk->exists($path) ? hash_file('sha256', $disk->path($path)) : null;

            if ($size <= 0) {
                throw new \RuntimeException(__('operations.backups.errors.empty_backup'));
            }

            $backup->update([
                'path'         => $path,
                'size_bytes'   => $size,
                'checksum'     => $checksum,
                'status'       => BackupStatus::Completed,
                'completed_at' => now(),
            ]);

            SystemOperationLog::record('backup.completed', $backup->fresh(), $user);

            return $backup->fresh();
        } catch (\Throwable $exception) {
            $backup->update([
                'status'         => BackupStatus::Failed,
                'failure_reason' => Str::limit($exception->getMessage(), 500),
                'completed_at'   => now(),
            ]);

            SystemOperationLog::record('backup.failed', $backup->fresh(), $user, [
                'reason' => $backup->failure_reason,
            ]);

            throw $exception;
        }
    }

    protected function backupDatabase(string $name, string $disk): string
    {
        $driver = (string) config('database.connections.'.config('database.default').'.driver');

        return match ($driver) {
            'mysql'  => $this->backupMysqlDatabase($name, $disk),
            'sqlite' => $this->backupSqliteDatabase($name, $disk),
            default  => throw new \RuntimeException(__('operations.backups.errors.unsupported_driver', ['driver' => $driver])),
        };
    }

    protected function backupMysqlDatabase(string $name, string $disk): string
    {
        $connection = config('database.connections.'.config('database.default'));
        $binary = (string) config('operations.backup.database.mysqldump_binary', 'mysqldump');
        $relativePath = $this->backupPath("{$name}.sql.gz");
        $absolutePath = Storage::disk($disk)->path($relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));

        $command = sprintf(
            '%s --single-transaction --quick --lock-tables=false -h %s -P %s -u %s %s | gzip > %s',
            escapeshellarg($binary),
            escapeshellarg((string) ($connection['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($connection['port'] ?? '3306')),
            escapeshellarg((string) ($connection['username'] ?? 'root')),
            escapeshellarg((string) ($connection['database'] ?? '')),
            escapeshellarg($absolutePath),
        );

        $env = [];

        if (filled($connection['password'] ?? null)) {
            $env['MYSQL_PWD'] = (string) $connection['password'];
        }

        $result = Process::env($env)->run(['bash', '-lc', $command]);

        if (! $result->successful()) {
            throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
        }

        return $relativePath;
    }

    protected function backupSqliteDatabase(string $name, string $disk): string
    {
        $connection = config('database.connections.'.config('database.default'));
        $databasePath = (string) ($connection['database'] ?? '');

        if (! File::exists($databasePath)) {
            throw new \RuntimeException(__('operations.backups.errors.sqlite_missing'));
        }

        $relativePath = $this->backupPath("{$name}.sqlite");
        Storage::disk($disk)->put($relativePath, File::get($databasePath));

        return $relativePath;
    }

    protected function backupFiles(string $name, string $disk): string
    {
        $relativePath = $this->backupPath("{$name}.zip");
        $absolutePath = Storage::disk($disk)->path($relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));

        $zip = new ZipArchive;

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(__('operations.backups.errors.zip_failed'));
        }

        foreach (config('operations.backup.files.include', ['private']) as $includeDisk) {
            $root = Storage::disk($includeDisk)->path('');

            $this->addDirectoryToZip(
                $zip,
                $root,
                basename($root),
                (array) config('operations.backup.files.exclude_patterns', []),
            );
        }

        $zip->close();

        return $relativePath;
    }

    protected function backupFull(string $name, string $disk): string
    {
        $databasePath = $this->backupDatabase("{$name}_db", $disk);
        $filesPath = $this->backupFiles("{$name}_files", $disk);
        $relativePath = $this->backupPath("{$name}.zip");
        $absolutePath = Storage::disk($disk)->path($relativePath);

        $zip = new ZipArchive;

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(__('operations.backups.errors.zip_failed'));
        }

        $storage = Storage::disk($disk);
        $zip->addFile($storage->path($databasePath), basename($databasePath));
        $zip->addFile($storage->path($filesPath), basename($filesPath));
        $zip->close();

        $storage->delete($databasePath);
        $storage->delete($filesPath);

        return $relativePath;
    }

    protected function backupPath(string $filename): string
    {
        return trim(config('operations.backup.path_prefix', 'backups'), '/').'/'.now()->format('Y/m/d').'/'.$filename;
    }

    /**
     * @param  array<int, string>  $excludePatterns
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $directory, string $localRoot, array $excludePatterns): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $path = $file->getPathname();
            $relative = $localRoot.'/'.ltrim(str_replace($directory, '', $path), DIRECTORY_SEPARATOR);

            foreach ($excludePatterns as $pattern) {
                if (str_contains($relative, $pattern)) {
                    continue 2;
                }
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);

                continue;
            }

            $zip->addFile($path, $relative);
        }
    }
}
