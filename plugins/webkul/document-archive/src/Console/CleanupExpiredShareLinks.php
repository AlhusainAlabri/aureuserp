<?php

namespace Webkul\DocumentArchive\Console;

use Illuminate\Console\Command;
use Webkul\DocumentArchive\Models\DocShareLink;

class CleanupExpiredShareLinks extends Command
{
    protected $signature = 'document-archive:cleanup-share-links';

    protected $description = 'Deactivate document share links whose expiry date is in the past';

    public function handle(): int
    {
        $count = DocShareLink::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        $this->info(__('document-archive::document-archive.commands.cleanup_share_links.done', ['count' => $count]));

        return self::SUCCESS;
    }
}
