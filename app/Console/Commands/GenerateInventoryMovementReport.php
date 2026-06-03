<?php

namespace App\Console\Commands;

use App\Mail\InventoryMovementReportMail;
use App\Services\Inventory\InventoryMovementReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;

class GenerateInventoryMovementReport extends Command
{
    protected $signature = 'inventory:movement-report {--days=7 : Number of days to include}';

    protected $description = 'Generate and email periodic inventory movement report';

    public function handle(InventoryMovementReportService $reportService): int
    {
        if (! Schema::hasTable('inventories_moves')) {
            $this->warn('Inventory moves table not found.');

            return self::SUCCESS;
        }

        $days = (int) $this->option('days');
        $from = now()->subDays($days)->startOfDay();
        $to = now()->endOfDay();

        $pdfPath = $reportService->storePdf($from, $to);

        $recipients = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                'super_admin',
                'general_manager',
                'finance_manager',
                'Admin',
            ]))
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('No recipients found for inventory movement report.');

            return self::SUCCESS;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue(new InventoryMovementReportMail($from, $to, $pdfPath));
        }

        $this->info("Queued inventory movement report for {$recipients->count()} recipient(s).");

        return self::SUCCESS;
    }
}
