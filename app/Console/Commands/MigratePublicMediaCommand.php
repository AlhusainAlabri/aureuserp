<?php

namespace App\Console\Commands;

use App\Support\Media\PublicMediaUrl;
use Illuminate\Console\Command;

class MigratePublicMediaCommand extends Command
{
    protected $signature = 'media:migrate-public-files';

    protected $description = 'Copy public avatar and logo files from the local disk to the public disk';

    public function handle(): int
    {
        $result = PublicMediaUrl::migrateFromLocalDisk();

        $this->info("Migrated {$result['migrated']} file(s) to the public disk.");

        if ($result['skipped'] > 0) {
            $this->line("Skipped {$result['skipped']} file(s) that already exist on the public disk.");
        }

        return self::SUCCESS;
    }
}
