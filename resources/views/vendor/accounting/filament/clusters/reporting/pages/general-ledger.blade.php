<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section>
            @php
                $data = $this->generalLedgerData;
            @endphp

            <x-slot name="heading">
                {{ __('accounting-extensions::reporting.general-ledger.heading', [
                    'from' => \App\Support\OmrFormatter::localizedDate($data['date_from']),
                    'to' => \App\Support\OmrFormatter::localizedDate($data['date_to']),
                ]) }}
            </x-slot>

            <x-slot name="afterHeader">
                @include('accounting::filament.clusters.reporting.partials.expand-collapse')
            </x-slot>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/5!">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5!">
                    <thead class="bg-gray-50/50 dark:bg-white/5">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"></th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.account') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.date') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.communication') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.partner') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.debit') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.credit') }}</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.balance') }}</th>
                        </tr>
                    </thead>

                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp

                    @if($data['accounts']->isNotEmpty())
                        @foreach($data['accounts'] as $account)
                            @php
                                $totalDebit += $account->period_debit;
                                $totalCredit += $account->period_credit;
                            @endphp

                            <tbody wire:key="account-{{ $account->id }}" class="divide-y divide-gray-200 dark:divide-white/5!">
                                <tr
                                    class="cursor-pointer bg-gray-50/50 hover:bg-gray-100/50 dark:bg-white/5 dark:hover:bg-white/5!"
                                    x-data="{ loading: false }"
                                    @click="loading = true; $wire.toggleAccountLines({{ $account->id }}).then(() => loading = false)"
                                >
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center">
                                            <svg x-show="!loading" @class(['h-4 w-4 transition-transform', 'rotate-90' => $this->isAccountExpanded($account->id)]) fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            <x-filament::loading-indicator x-show="loading" x-cloak class="h-4 w-4" />
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $account->code }} {{ $account->name }}</td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">{{ \App\Support\OmrFormatter::format($account->period_debit) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">{{ \App\Support\OmrFormatter::format($account->period_credit) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ \App\Support\OmrFormatter::format($account->ending_balance) }}</td>
                                </tr>

                                @if($account->opening_balance != 0 && $this->isAccountExpanded($account->id))
                                    <tr class="bg-white dark:bg-gray-900">
                                        <td class="px-4 py-2"></td>
                                        <td class="whitespace-nowrap px-4 py-2 pl-8 text-sm italic text-gray-600 dark:text-gray-400">{{ __('accounting-extensions::reporting.opening-balance') }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ \App\Support\OmrFormatter::localizedDate($data['date_from']) }}</td>
                                        <td class="px-4 py-2"></td>
                                        <td class="px-4 py-2"></td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-400">{{ $account->opening_balance > 0 ? \App\Support\OmrFormatter::format($account->opening_balance) : '' }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-400">{{ $account->opening_balance < 0 ? \App\Support\OmrFormatter::format(abs($account->opening_balance)) : '' }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-semibold text-gray-600 dark:text-gray-400">{{ \App\Support\OmrFormatter::format($account->opening_balance) }}</td>
                                    </tr>
                                @endif

                                @php $runningBalance = $account->opening_balance; @endphp

                                @if($this->isAccountExpanded($account->id))
                                    @foreach($this->getAccountMoves($account->id) as $move)
                                        @php $runningBalance += ($move['debit'] - $move['credit']); @endphp
                                        <tr class="bg-white dark:bg-gray-900">
                                            <td class="px-4 py-2"></td>
                                            <td class="whitespace-nowrap px-4 py-2 pl-8 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $move['move_name'] }}
                                                @if($move['ref'])<span class="text-xs text-gray-500">({{ $move['ref'] }})</span>@endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ \App\Support\OmrFormatter::localizedDate($move['date']) }}</td>
                                            <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600 dark:text-gray-400">@if ($move['move_type'] == 'entry'){{ $move['name'] }}@endif</td>
                                            <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $move['partner_name'] }}</td>
                                            <td class="whitespace-nowrap px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-400">{{ $move['debit'] > 0 ? \App\Support\OmrFormatter::format($move['debit']) : '' }}</td>
                                            <td class="whitespace-nowrap px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-400">{{ $move['credit'] > 0 ? \App\Support\OmrFormatter::format($move['credit']) : '' }}</td>
                                            <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-medium text-gray-600 dark:text-gray-400">{{ \App\Support\OmrFormatter::format($runningBalance) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        @endforeach

                        <tbody>
                            <tr class="border-t-2 border-gray-300 bg-gray-100/80 font-semibold dark:border-white/5! dark:bg-white/5">
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ __('accounting-extensions::reporting.total-general-ledger') }}</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">{{ \App\Support\OmrFormatter::format($totalDebit) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">{{ \App\Support\OmrFormatter::format($totalCredit) }}</td>
                                <td class="px-4 py-3"></td>
                            </tr>
                        </tbody>
                    @else
                        <tbody>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.no-accounts') }}</td>
                            </tr>
                        </tbody>
                    @endif
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
