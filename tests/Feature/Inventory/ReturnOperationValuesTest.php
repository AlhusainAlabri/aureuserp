<?php

use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Inventory\Enums\OperationType as OperationTypeKind;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'inventories' => 'inventories_operations',
    ]);
});

it('sends a vendor return back to the vendor location', function (): void {
    $vendors = new Location(['name' => 'Vendors']);
    $vendors->id = 11;

    $stock = new Location(['name' => 'Stock']);
    $stock->id = 12;

    $deliveryType = new OperationType(['type' => OperationTypeKind::OUTGOING]);
    $deliveryType->id = 21;

    $receiptType = new OperationType(['type' => OperationTypeKind::INCOMING]);
    $receiptType->id = 22;
    $receiptType->setRelation('returnOperationType', $deliveryType);

    $receipt = new Operation;
    $receipt->id = 31;
    $receipt->setRelation('operationType', $receiptType);
    $receipt->setRelation('sourceLocation', $vendors);
    $receipt->setRelation('destinationLocation', $stock);

    $values = Inventory::prepareReturnOperationValues($receipt);

    expect($values['source_location_id'])->toBe(12)
        ->and($values['destination_location_id'])->toBe(11)
        ->and($values)->not->toHaveKey('location_destination_id');
});
