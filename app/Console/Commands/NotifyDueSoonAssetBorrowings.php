<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Assets\Enums\BorrowingStatus;
use Webkul\Assets\Jobs\NotifyDueSoonAssetBorrowingJob;
use Webkul\Assets\Models\AssetBorrowing;

class NotifyDueSoonAssetBorrowings extends Command
{
    protected $signature = 'assets:notify-due-soon';

    protected $description = 'Notify employees and HR about asset borrowings due within 3 days';

    public function handle(): int
    {
        if (! Schema::hasTable('asset_borrowings')) {
            $this->error(__('assets::assets.commands.overdue.table_missing'));

            return self::FAILURE;
        }

        $borrowings = AssetBorrowing::query()
            ->with(['asset', 'employee.user'])
            ->where('status', BorrowingStatus::Active)
            ->whereNull('due_reminder_sent_at')
            ->whereBetween('due_at', [now(), now()->addDays(3)])
            ->get();

        foreach ($borrowings as $borrowing) {
            NotifyDueSoonAssetBorrowingJob::dispatch($borrowing);
            $borrowing->update(['due_reminder_sent_at' => now()]);
        }

        $this->info(__('assets-extensions::commands.due_soon.done', [
            'count' => $borrowings->count(),
        ]));

        return self::SUCCESS;
    }
}
