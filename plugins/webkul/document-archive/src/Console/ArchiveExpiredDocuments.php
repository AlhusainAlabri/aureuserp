<?php

namespace Webkul\DocumentArchive\Console;

use Illuminate\Console\Command;
use Webkul\DocumentArchive\Models\DocFile;

class ArchiveExpiredDocuments extends Command
{
    protected $signature = 'document-archive:archive-expired';

    protected $description = 'Soft-delete document archive files past their expiry date';

    public function handle(): int
    {
        $count = 0;

        DocFile::query()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->toDateString())
            ->get()
            ->each(function (DocFile $file) use (&$count): void {
                $file->activities()->create([
                    'user_id'  => $file->creator_id ?? 0,
                    'action'   => 'deleted',
                    'metadata' => ['reason' => 'expired'],
                ]);

                $file->delete();
                $count++;
            });

        $this->info(__('document-archive::document-archive.commands.archive_expired.done', ['count' => $count]));

        return self::SUCCESS;
    }
}
