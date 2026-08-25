<?php

namespace App\Services\Operations;

use App\Enums\Operations\BackupStatus;
use App\Models\Operations\SystemBackup;
use App\Support\Operations\OperationsSettingsResolver;
use Illuminate\Support\Facades\Storage;

class BackupRetentionService
{
    public function clean(): int
    {
        $settings = OperationsSettingsResolver::resolve();

        $keep = max($settings->backup_retention_count, 1);

        $expired = SystemBackup::query()
            ->where('status', BackupStatus::Completed)
            ->orderByDesc('completed_at')
            ->skip($keep)
            ->take(PHP_INT_MAX)
            ->get();

        $deleted = 0;

        foreach ($expired as $backup) {
            if ($this->deleteBackupFile($backup)) {
                $backup->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    public function delete(SystemBackup $backup): bool
    {
        $this->deleteBackupFile($backup);

        return (bool) $backup->delete();
    }

    protected function deleteBackupFile(SystemBackup $backup): bool
    {
        if ($backup->path === '') {
            return true;
        }

        $disk = Storage::disk($backup->disk);

        if ($disk->exists($backup->path)) {
            $disk->delete($backup->path);
        }

        return true;
    }
}
