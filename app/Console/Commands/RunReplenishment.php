<?php

namespace App\Console\Commands;

use App\Services\Inventory\ReplenishmentProcurementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Enums\OrderPointTrigger;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;
use Webkul\Inventory\Models\OrderPoint;

class RunReplenishment extends Command
{
    protected $signature = 'inventory:run-replenishment';

    protected $description = 'Process automatic replenishment rules and create procurement documents';

    public function handle(ReplenishmentProcurementService $service): int
    {
        if (! Schema::hasTable('inventories_order_points')) {
            $this->warn('Inventory order points table not found.');

            return self::SUCCESS;
        }

        $processed = 0;

        OrderPoint::query()
            ->where('trigger', OrderPointTrigger::AUTOMATIC)
            ->each(function (OrderPoint $orderPoint) use ($service, &$processed): void {
                if (ReplenishmentResource::qtyToOrder($orderPoint) <= 0) {
                    return;
                }

                $order = $service->processAutomaticReplenishment($orderPoint);

                if ($order !== null) {
                    $processed++;
                    $this->info("Created procurement {$order->name} for product #{$orderPoint->product_id}.");
                }
            });

        $this->info("Processed {$processed} automatic replenishment rule(s).");

        return self::SUCCESS;
    }
}
