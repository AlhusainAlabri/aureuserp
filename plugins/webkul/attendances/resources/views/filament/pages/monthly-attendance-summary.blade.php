<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section :heading="__('attendances::attendances.monthly.title')">
            @if ($this->report['truncated'])
                <p class="mb-4 rounded-lg bg-warning-50 px-3 py-2 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                    {{ __('attendances::attendances.monthly.capped', ['shown' => count($this->report['rows']), 'total' => $this->report['totalEmployees']]) }}
                </p>
            @endif

            @if ($this->rows->isEmpty())
                <p class="text-sm text-gray-500">{{ __('attendances::attendances.monthly.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="px-3 py-2 text-start">{{ __('attendances::attendances.monthly.columns.employee') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('attendances::attendances.monthly.columns.working_days') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('attendances::attendances.monthly.columns.absent_days') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('attendances::attendances.monthly.columns.late_minutes') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('attendances::attendances.monthly.columns.overtime_hours') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->rows as $row)
                                <tr class="border-b" wire:key="attendance-summary-{{ $row['employee_id'] }}">
                                    <td class="px-3 py-2">{{ $row['employee'] }}</td>
                                    @if ($row['has_schedule'])
                                        <td class="px-3 py-2 text-end tabular-nums">{{ $row['working_days'] }}</td>
                                        <td class="px-3 py-2 text-end tabular-nums">{{ $row['absent_days'] }}</td>
                                        <td class="px-3 py-2 text-end tabular-nums">{{ $row['late_minutes'] }}</td>
                                        <td class="px-3 py-2 text-end tabular-nums" @if ($row['overtime_by_multiplier'] !== []) title="{{ $this->formatBreakdown($row['overtime_by_multiplier']) }}" @endif>
                                            {{ number_format($row['overtime_hours'], 2, '.', '') }}
                                            @if ($row['overtime_by_multiplier'] !== [])
                                                <span class="block text-xs text-gray-500" dir="ltr">{{ $this->formatBreakdown($row['overtime_by_multiplier']) }}</span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="px-3 py-2 text-end text-gray-400" colspan="4" title="{{ __('attendances::attendances.monthly.no_schedule') }}">—</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td class="px-3 py-2">{{ __('attendances::attendances.monthly.totals') }}</td>
                                <td class="px-3 py-2 text-end tabular-nums">{{ $this->totals['working_days'] }}</td>
                                <td class="px-3 py-2 text-end tabular-nums">{{ $this->totals['absent_days'] }}</td>
                                <td class="px-3 py-2 text-end tabular-nums">{{ $this->totals['late_minutes'] }}</td>
                                <td class="px-3 py-2 text-end tabular-nums">
                                    {{ number_format($this->totals['overtime_hours'], 2, '.', '') }}
                                    @if ($this->totals['overtime_by_multiplier'] !== [])
                                        <span class="block text-xs font-normal text-gray-500" dir="ltr">{{ $this->formatBreakdown($this->totals['overtime_by_multiplier']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p class="mt-3 text-xs text-gray-500">{{ __('attendances::attendances.monthly.employees_counted', ['count' => $this->totals['employees']]) }}</p>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
