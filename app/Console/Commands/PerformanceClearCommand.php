<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class PerformanceClearCommand extends Command
{
    protected $signature = 'performance:clear
                            {--days= : Delete logs older than this many days}
                            {--all : Delete all performance logs}';

    protected $description = 'Clear recorded performance log files';

    public function handle(): int
    {
        $directory = (string) config('performance.slow_query_log.directory', storage_path('logs/performance'));

        if (! is_dir($directory)) {
            $this->info('No performance logs directory found.');

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            File::cleanDirectory($directory);
            $this->info('All performance logs cleared.');

            return self::SUCCESS;
        }

        $days = (int) ($this->option('days') ?: config('performance.slow_query_log.retention_days', 14));
        $cutoff = now()->subDays($days)->startOfDay();
        $deleted = 0;

        foreach (glob($directory.'/requests-*.jsonl') ?: [] as $file) {
            if (! preg_match('/requests-(\d{4}-\d{2}-\d{2})\.jsonl$/', $file, $matches)) {
                continue;
            }

            $fileDate = Carbon::parse($matches[1])->startOfDay();

            if ($fileDate->lt($cutoff)) {
                unlink($file);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} performance log file(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
