<?php

namespace App\Console\Commands;

use App\Mail\InventoryLowStockMail;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReplenishmentResource;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Security\Models\User;

class NotifyLowStock extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Notify inventory managers about products below minimum stock levels';

    public function handle(): int
    {
        if (! Schema::hasTable('inventories_order_points')) {
            $this->warn('Inventory order points table not found.');

            return self::SUCCESS;
        }

        $lowStockPoints = OrderPoint::query()
            ->with(['product', 'location'])
            ->get()
            ->filter(fn (OrderPoint $point): bool => ReplenishmentResource::isBelowMinimum($point));

        if ($lowStockPoints->isEmpty()) {
            $this->info('No low-stock items found.');

            return self::SUCCESS;
        }

        $replenishmentUrl = ReplenishmentResource::getUrl('index', [
            'activeTableView' => 'below_minimum',
        ]);

        $managers = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                'super_admin',
                'general_manager',
                'finance_manager',
                'Admin',
            ]))
            ->whereNotNull('email')
            ->get();

        if ($managers->isEmpty()) {
            $managers = User::query()->whereNotNull('email')->limit(5)->get();
        }

        $count = $lowStockPoints->count();
        $productNames = $lowStockPoints
            ->take(5)
            ->map(fn (OrderPoint $point): string => $point->product?->name ?? $point->name)
            ->implode(', ');

        foreach ($managers as $manager) {
            Notification::make()
                ->title(__('inventory-extensions::notifications.low_stock_title', ['count' => $count]))
                ->body(__('inventory-extensions::notifications.low_stock_body', [
                    'products' => $productNames,
                ]))
                ->warning()
                ->icon('heroicon-o-exclamation-triangle')
                ->actions([
                    Action::make('view')
                        ->label(__('inventory-extensions::mail.low_stock_action'))
                        ->url($replenishmentUrl),
                ])
                ->sendToDatabase($manager);

            Mail::to($manager)->queue(new InventoryLowStockMail($lowStockPoints, $replenishmentUrl));
        }

        $this->info("Notified {$managers->count()} user(s) about {$count} low-stock item(s).");

        return self::SUCCESS;
    }
}
