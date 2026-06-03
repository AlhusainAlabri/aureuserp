<x-filament-panels::page>
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('inventory-extensions::purchase_history.supplier') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('inventory-extensions::purchase_history.last_purchase_date') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('inventory-extensions::purchase_history.last_price') }}</th>
                    <th class="px-4 py-3 text-start font-semibold">{{ __('inventory-extensions::purchase_history.total_qty') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($this->historyRows as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row['supplier_name'] }}</td>
                        <td class="px-4 py-3">{{ $row['last_purchase_date']?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ \App\Services\Inventory\ProductPurchaseHistoryService::formatOmr($row['last_price']) }}</td>
                        <td class="px-4 py-3">{{ number_format($row['total_qty'], 3) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                            {{ __('inventory-extensions::purchase_history.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
