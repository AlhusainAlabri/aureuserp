<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section>
            @php
                $data = $this->trialBalanceData;
            @endphp

            <x-slot name="heading">
                {{ __('accounting-extensions::reporting.trial-balance.heading', [
                    'from' => \App\Support\OmrFormatter::localizedDate($data['date_from']),
                    'to' => \App\Support\OmrFormatter::localizedDate($data['date_to']),
                ]) }}
            </x-slot>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/5!">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5!">
                    <colgroup>
                        <col style="width: auto;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                    </colgroup>

                    <thead class="bg-gray-50/50 dark:bg-white/5">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"></th>
                            <th colspan="2" scope="col" class="border-b border-gray-200 px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-white/5! dark:text-gray-400">
                                {{ __('accounting-extensions::reporting.initial-balance') }}
                            </th>
                            <th colspan="2" scope="col" class="border-b border-gray-200 px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-white/5! dark:text-gray-400">
                                {{ __('accounting-extensions::reporting.period-range', [
                                    'from' => \App\Support\OmrFormatter::localizedDate($data['date_from'], 'j M Y'),
                                    'to' => \App\Support\OmrFormatter::localizedDate($data['date_to'], 'j M Y'),
                                ]) }}
                            </th>
                            <th colspan="2" scope="col" class="border-b border-gray-200 px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-white/5! dark:text-gray-400">
                                {{ __('accounting-extensions::reporting.end-balance') }}
                            </th>
                        </tr>

                        <tr class="border-b border-gray-200 dark:border-white/5!">
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('accounting-extensions::reporting.account') }}
                            </th>
                            @foreach (range(1, 6) as $ignored)
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ $loop->iteration % 2 === 1 ? __('accounting-extensions::reporting.debit') : __('accounting-extensions::reporting.credit') }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-white/5!">
                        @if($data['accounts']->isNotEmpty())
                            @foreach($data['accounts'] as $account)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5!">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100!">
                                        {{ $account->code ? $account->code . ' ' : '' }}{{ $account->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100!">
                                        {{ \App\Support\OmrFormatter::format($account->initial_debit) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100!">
                                        {{ \App\Support\OmrFormatter::format($account->initial_credit) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100!">
                                        {{ \App\Support\OmrFormatter::format($account->period_debit) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100!">
                                        {{ \App\Support\OmrFormatter::format($account->period_credit) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100!">
                                        {{ \App\Support\OmrFormatter::format($account->end_debit) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100!">
                                        {{ \App\Support\OmrFormatter::format($account->end_credit) }}
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="border-t-2 border-gray-300 bg-gray-100/80 font-semibold dark:border-white/5! dark:bg-white/5">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ __('accounting-extensions::reporting.total') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($data['totals']['initial_debit']) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($data['totals']['initial_credit']) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($data['totals']['period_debit']) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($data['totals']['period_credit']) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($data['totals']['end_debit']) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($data['totals']['end_credit']) }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    {{ __('accounting-extensions::reporting.no-accounts') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
