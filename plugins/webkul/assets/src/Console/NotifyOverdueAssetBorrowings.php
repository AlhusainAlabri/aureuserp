<?php

namespace Webkul\Assets\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Jobs\NotifyOverdueAssetBorrowingJob;
use Webkul\Assets\Models\AssetBorrowing;

class NotifyOverdueAssetBorrowings extends Command
{
    protected $signature = 'assets:notify-overdue-borrowings';

    protected $description = 'Mark overdue asset borrowings and notify HR managers.';

    public function handle(): int
    {
        if (! Schema::hasTable('asset_borrowings')) {
            $this->warn(__('assets::assets.commands.overdue.table_missing'));

            return self::SUCCESS;
        }

        $count = 0;

        AssetBorrowing::query()
            ->where('status', BorrowingStatus::Active)
            ->where('due_at', '<', now())
            ->with(['asset', 'employee'])
            ->each(function (AssetBorrowing $borrowing) use (&$count): void {
                NotifyOverdueAssetBorrowingJob::dispatch($borrowing);
                $count++;
            });

        $this->info(__('assets::assets.commands.overdue.done', ['count' => $count]));

        return self::SUCCESS;
    }
}
