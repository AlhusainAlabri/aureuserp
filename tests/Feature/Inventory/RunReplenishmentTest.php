<?php

use App\Console\Commands\RunReplenishment;
use Illuminate\Support\Facades\Schema;

it('runs automatic replenishment command', function (): void {
    if (! Schema::hasTable('inventories_order_points')) {
        $this->markTestSkipped('Inventory not installed.');
    }

    $this->artisan(RunReplenishment::class)->assertSuccessful();
});
