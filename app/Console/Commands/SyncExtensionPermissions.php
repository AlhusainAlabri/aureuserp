<?php

namespace App\Console\Commands;

use App\Support\ExtensionPermissionsSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('permissions:sync-extensions')]
#[Description('Sync extension module permissions and role assignments (idempotent — run on install/deploy)')]
class SyncExtensionPermissions extends Command
{
    public function handle(ExtensionPermissionsSync $sync): int
    {
        $sync->syncAll();

        $this->components->info('Extension permissions synced successfully.');

        return self::SUCCESS;
    }
}
