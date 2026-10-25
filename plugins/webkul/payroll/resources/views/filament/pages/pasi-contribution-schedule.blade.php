<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section :heading="__('payroll::payroll.pasi.schedule.title')">
            @if ($this->rows->isEmpty())
                <p class="text-sm text-gray-500">{{ __('payroll::payroll.pasi.schedule.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="px-3 py-2 text-start">{{ __('payroll::payroll.pasi.schedule.columns.employee') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('payroll::payroll.pasi.schedule.columns.civil_id') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('payroll::payroll.pasi.schedule.columns.category') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('payroll::payroll.pasi.schedule.columns.insurable_salary') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('payroll::payroll.pasi.schedule.columns.employee_share') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('payroll::payroll.pasi.schedule.columns.employer_share') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->rows as $row)
                                <tr class="border-b">
                                    <td class="px-3 py-2">{{ $row['employee'] }}</td>
                                    <td class="px-3 py-2" dir="ltr">{{ $row['civil_id'] }}</td>
                                    <td class="px-3 py-2">{{ $row['category'] }}</td>
                                    <td class="px-3 py-2 text-end">{{ $this->formatMoney($row['insurable_salary']) }}</td>
                                    <td class="px-3 py-2 text-end">{{ $this->formatMoney($row['employee_share']) }}</td>
                                    <td class="px-3 py-2 text-end">{{ $this->formatMoney($row['employer_share']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td class="px-3 py-2" colspan="3">{{ __('payroll::payroll.pasi.schedule.totals') }}</td>
                                <td class="px-3 py-2 text-end">{{ $this->formatMoney($this->totals['insurable_salary']) }}</td>
                                <td class="px-3 py-2 text-end">{{ $this->formatMoney($this->totals['employee_share']) }}</td>
                                <td class="px-3 py-2 text-end">{{ $this->formatMoney($this->totals['employer_share']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
