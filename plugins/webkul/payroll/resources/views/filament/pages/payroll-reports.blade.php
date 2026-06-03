<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <p class="text-sm text-gray-500">{{ __('payroll::payroll.reports.payslip_count') }}</p>
                <p class="text-2xl font-semibold">{{ $this->summary['payslip_count'] }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500">{{ __('payroll::payroll.reports.employee_count') }}</p>
                <p class="text-2xl font-semibold">{{ $this->summary['employee_count'] }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500">{{ __('payroll::payroll.reports.total_gross') }}</p>
                <p class="text-2xl font-semibold">{{ $this->formatMoney($this->summary['total_gross']) }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500">{{ __('payroll::payroll.reports.total_net') }}</p>
                <p class="text-2xl font-semibold">{{ $this->formatMoney($this->summary['total_net']) }}</p>
            </x-filament::section>
        </div>

        <x-filament::section :heading="__('payroll::payroll.reports.by_department')">
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament::page>
