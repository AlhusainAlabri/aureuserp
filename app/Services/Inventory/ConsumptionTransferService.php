<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryConsumptionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Enums\OperationType as OperationTypeEnum;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\InternalTransfer;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\OperationType;
use Webkul\Product\Models\Product;

class ConsumptionTransferService
{
    /**
     * @return array{operation: InternalTransfer, log: InventoryConsumptionLog}
     */
    public function recordConsumption(
        Product $product,
        float $quantity,
        string $purpose,
        ?int $departmentId = null,
        ?int $projectId = null,
        ?int $sourceLocationId = null,
    ): array {
        if (! Schema::hasTable('inventories_operations')) {
            throw new \RuntimeException(__('inventory-extensions::consumption.plugin_missing'));
        }

        $operationType = OperationType::query()
            ->where('type', OperationTypeEnum::INTERNAL)
            ->first();

        if (! $operationType) {
            throw new \RuntimeException(__('inventory-extensions::consumption.no_operation_type'));
        }

        $sourceLocationId ??= $operationType->source_location_id;
        $destinationLocationId = $this->resolveConsumptionLocationId($sourceLocationId);

        return DB::transaction(function () use (
            $product,
            $quantity,
            $purpose,
            $departmentId,
            $projectId,
            $operationType,
            $sourceLocationId,
            $destinationLocationId,
        ): array {
            /** @var InternalTransfer $operation */
            $operation = InternalTransfer::query()->create([
                'name'                    => __('inventory-extensions::consumption.operation_name', ['product' => $product->name]),
                'origin'                  => $purpose,
                'state'                   => OperationState::DRAFT,
                'operation_type_id'       => $operationType->id,
                'source_location_id'      => $sourceLocationId,
                'destination_location_id' => $destinationLocationId,
                'company_id'              => $operationType->destinationLocation?->company_id ?? Auth::user()?->default_company_id,
                'user_id'                 => Auth::id(),
                'creator_id'              => Auth::id(),
                'scheduled_at'            => now(),
            ]);

            $move = Move::query()->create([
                'name'                    => $product->name,
                'state'                   => MoveState::DRAFT,
                'product_id'              => $product->id,
                'uom_id'                  => $product->uom_id,
                'product_qty'             => $quantity,
                'product_uom_qty'         => $quantity,
                'quantity'                => $quantity,
                'source_location_id'      => $sourceLocationId,
                'destination_location_id' => $destinationLocationId,
                'operation_id'            => $operation->id,
                'operation_type_id'       => $operationType->id,
                'company_id'              => $operation->company_id,
                'creator_id'              => Auth::id(),
                'scheduled_at'            => now(),
            ]);

            try {
                $operation = Inventory::confirmTransfer($operation->fresh(['moves']));
                $operation = Inventory::assignTransfer($operation);
                $move->refresh()->update(['quantity' => $quantity, 'is_picked' => true]);
                $operation = Inventory::doneTransfer($operation->fresh(['moves']));
            } catch (\Throwable) {
                // Leave draft operation when stock is unavailable; log still records intent.
            }

            $log = InventoryConsumptionLog::query()->create([
                'move_id'       => $move->id,
                'operation_id'  => $operation->id,
                'product_id'    => $product->id,
                'quantity'      => $quantity,
                'department_id' => $departmentId,
                'project_id'    => $projectId,
                'purpose'       => $purpose,
                'recorded_by'   => Auth::id(),
                'company_id'    => $operation->company_id,
            ]);

            return [
                'operation' => $operation->fresh(['moves']),
                'log'       => $log,
            ];
        });
    }

    public function resolveConsumptionLocationId(?int $sourceLocationId = null): int
    {
        $consumptionName = __('inventory-extensions::locations.consumption');

        $location = Location::query()
            ->when($sourceLocationId, fn ($query) => $query->where('parent_id', $sourceLocationId))
            ->where('name', $consumptionName)
            ->first();

        if ($location) {
            return $location->id;
        }

        $fallback = OperationType::query()
            ->where('type', OperationTypeEnum::INTERNAL)
            ->value('destination_location_id');

        if (! $fallback) {
            throw new \RuntimeException(__('inventory-extensions::consumption.no_destination'));
        }

        return (int) $fallback;
    }
}
