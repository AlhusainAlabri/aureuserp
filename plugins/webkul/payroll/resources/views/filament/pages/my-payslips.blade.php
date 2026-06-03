<x-filament::page>
    <div class="space-y-6">
        @if ($this->payslips->isEmpty())
            <x-filament::section>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <x-filament::icon icon="heroicon-o-document-duplicate" class="h-16 w-16 text-gray-300 mb-4" />
                    <p class="text-lg font-medium text-gray-700 dark:text-gray-200">
                        {{ __('payroll::payroll.my_payslips.empty.title') }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('payroll::payroll.my_payslips.empty.description') }}
                    </p>
                </div>
            </x-filament::section>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->payslips as $payslip)
                    <x-filament::section>
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs text-gray-500 font-mono">{{ $payslip->reference_number }}</p>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                        {{ __('payroll::payroll.my_payslips.period') }}:
                                        {{ sprintf('%02d/%d', $payslip->period_month, $payslip->period_year) }}
                                    </h3>
                                </div>
                                <x-filament::badge :color="$payslip->status->getColor()">
                                    {{ $payslip->status->getLabel() }}
                                </x-filament::badge>
                            </div>

                            <dl class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">{{ __('payroll::payroll.fields.gross_amount') }}</dt>
                                    <dd class="font-medium">{{ $this->formatMoney((float) $payslip->gross_amount) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('payroll::payroll.fields.net_amount') }}</dt>
                                    <dd class="font-medium">{{ $this->formatMoney((float) $payslip->net_amount) }}</dd>
                                </div>
                            </dl>

                            <div class="flex justify-end">
                                <x-filament::button
                                    wire:click="downloadPayslip({{ $payslip->id }})"
                                    icon="heroicon-o-arrow-down-tray"
                                    size="sm"
                                >
                                    {{ __('payroll::payroll.my_payslips.download') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament::page>
