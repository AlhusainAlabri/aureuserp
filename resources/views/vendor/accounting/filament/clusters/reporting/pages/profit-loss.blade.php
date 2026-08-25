<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section>
            @php
                $data = $this->profitLossData;
            @endphp

            <x-slot name="heading">
                {{ __('accounting-extensions::reporting.profit-loss.heading', [
                    'from' => \App\Support\OmrFormatter::localizedDate($data['date_from']),
                    'to' => \App\Support\OmrFormatter::localizedDate($data['date_to']),
                ]) }}
            </x-slot>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/5!">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5!">
                    <thead class="bg-gray-50/50 dark:bg-white/5">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('accounting-extensions::reporting.account') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('accounting-extensions::reporting.amount') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-white/5!">
                        @foreach($data['sections'] as $section)
                            <tr class="bg-gray-100/80 dark:bg-white/5">
                                <td colspan="2" class="px-4 py-3 text-base font-bold text-gray-900 dark:text-white">
                                    {{ $section['title'] }}
                                </td>
                            </tr>

                            @if(count($section['accounts']) > 0)
                                @foreach($section['accounts'] as $account)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5!">
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400" style="padding-left: 2rem;">
                                            {{ $account['code'] ? $account['code'] . ' - ' : '' }}{{ $account['name'] }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm {{ isset($section['is_expense']) && $section['is_expense'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100!' }}">
                                            {{ \App\Support\OmrFormatter::format($account['balance']) }}
                                        </td>
                                    </tr>
                                @endforeach

                                <tr class="border-t border-gray-200 bg-gray-50/50 font-semibold dark:border-gray-700 dark:bg-gray-900/50">
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $section['total_label'] }}</td>
                                    <td class="px-4 py-2 text-right {{ isset($section['is_expense']) && $section['is_expense'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ \App\Support\OmrFormatter::format($section['total']) }}
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="2" class="px-4 py-2 text-sm italic text-gray-500 dark:text-gray-400" style="padding-left: 2rem;">
                                        {{ $section['empty_message'] }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach

                        <tr class="border-t-2 border-gray-300 bg-gray-100/80 font-bold dark:border-white/5! dark:bg-white/5">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                {{ $data['is_profit'] ? __('accounting-extensions::reporting.net-profit') : __('accounting-extensions::reporting.net-loss') }}
                            </td>
                            <td class="px-4 py-3 text-right {{ $data['is_profit'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ \App\Support\OmrFormatter::format(abs($data['net_income'])) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
