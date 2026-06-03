<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.widgets.my_payslip') }}
        </x-slot>

        @if ($latestPayslip)
            <div class="space-y-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ sprintf('%02d/%d', $latestPayslip->period_month, $latestPayslip->period_year) }}
                </p>
                <p class="text-2xl font-bold text-primary-600">
                    ر.ع. {{ number_format((float) $latestPayslip->net_amount, 3) }}
                </p>
                <div class="flex gap-2 pt-2">
                    <x-filament::button tag="a" :href="$this->getMyPayslipsUrl()" size="sm" color="gray" icon="heroicon-o-eye">
                        {{ __('payroll::payroll.my_payslips.view') }}
                    </x-filament::button>
                    <x-filament::button wire:click="downloadPdf" size="sm" icon="heroicon-o-arrow-down-tray">
                        {{ __('payroll::payroll.actions.download') }}
                    </x-filament::button>
                </div>
            </div>
        @else
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('payroll::payroll.my_payslips.empty.title') }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('payroll::payroll.my_payslips.empty.description') }}
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
