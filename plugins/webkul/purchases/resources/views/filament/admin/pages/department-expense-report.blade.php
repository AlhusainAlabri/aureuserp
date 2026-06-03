<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-filament::section>
                <div class="text-sm text-gray-500">
                    {{ __('purchases::filament/admin/pages/department-expense-report.cards.total-purchases') }}
                </div>
                <div class="text-2xl font-bold">
                    {{ $this->summaryData['total_purchases'] }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">
                    {{ __('purchases::filament/admin/pages/department-expense-report.cards.total-amount') }}
                </div>
                <div class="text-2xl font-bold">
                    {{ \App\Filament\Extensions\PurchaseOrderResourceExtensions::formatOmrAmount($this->summaryData['total_amount']) }}
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">
                    {{ __('purchases::filament/admin/pages/department-expense-report.cards.missing-receipts') }}
                </div>
                <div class="text-2xl font-bold {{ $this->summaryData['missing_receipts'] > 0 ? 'text-danger-600' : 'text-success-600' }}">
                    {{ $this->summaryData['missing_receipts'] }}
                </div>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament::page>
