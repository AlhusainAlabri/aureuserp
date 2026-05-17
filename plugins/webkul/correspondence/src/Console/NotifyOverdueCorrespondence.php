<?php

namespace Webkul\Correspondence\Console;

use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Security\Models\User;

class NotifyOverdueCorrespondence extends Command
{
    protected $signature = 'correspondence:notify-overdue';

    protected $description = 'Notify creators and recipients about overdue correspondence.';

    public function handle(): int
    {
        Correspondence::query()
            ->overdue()
            ->with(['creator', 'toUser'])
            ->chunkById(100, function ($correspondences): void {
                foreach ($correspondences as $correspondence) {
                    $users = User::query()
                        ->whereIn('id', array_filter([$correspondence->creator_id, $correspondence->to_user_id]))
                        ->get();

                    if ($users->isEmpty()) {
                        continue;
                    }

                    Notification::make()
                        ->title(__('correspondence::correspondence.notify.overdue.title'))
                        ->body(__('correspondence::correspondence.notify.overdue.body', [
                            'reference' => $correspondence->reference_number,
                            'date'      => $correspondence->due_date?->format('Y-m-d'),
                        ]))
                        ->sendToDatabase($users);
                }
            });

        $this->info(__('correspondence::correspondence.commands.overdue_complete'));

        return self::SUCCESS;
    }
}
