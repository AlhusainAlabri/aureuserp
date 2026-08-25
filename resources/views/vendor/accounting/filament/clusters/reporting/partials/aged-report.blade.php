@php
    $emptyMessageKey = $reportKey === 'aged-payable' ? 'no-payables' : 'no-receivables';
    $totalLabelKey = $reportKey === 'aged-payable' ? 'total-aged-payable' : 'total-aged-receivable';
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="$refresh">
            {{ $this->form }}
        </form>

        @php
            $data = $reportKey === 'aged-payable' ? $this->agedPayableData() : $this->agedReceivableData();
            $partners = $data['partners'];
            $asOfDate = $data['as_of_date'];
            $period = $data['period'];
            $hasUnposted = $data['has_unposted'];
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                {{ __("accounting-extensions::reporting.{$reportKey}.heading", [
                    'date' => \App\Support\OmrFormatter::localizedDate($asOfDate),
                ]) }}
            </x-slot>

            <x-slot name="afterHeader">
                @include('accounting::filament.clusters.reporting.partials.expand-collapse')
            </x-slot>

            @if($hasUnposted)
                <x-filament::badge color="warning" size="xl" class="mb-4 w-full justify-start px-4 py-2 text-sm!">
                    {{ __('accounting-extensions::reporting.unposted-warning') }}
                </x-filament::badge>
            @endif

            @if(empty($partners))
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    {{ __("accounting-extensions::reporting.{$emptyMessageKey}") }}
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/5!">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-white/5!">
                        <thead class="bg-gray-50/50 dark:bg-white/5">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400" style="width: 50px;"></th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __("accounting-extensions::reporting.{$reportKey}.title") }}</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.invoice-date') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.at-date') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">1-{{ $period }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $period + 1 }}-{{ $period * 2 }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $period * 2 + 1 }}-{{ $period * 3 }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $period * 3 + 1 }}-{{ $period * 4 }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.older') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accounting-extensions::reporting.total') }}</th>
                            </tr>
                        </thead>

                        @php
                            $totals = array_fill_keys(['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older', 'total'], 0);
                        @endphp

                        @foreach($partners as $partnerId => $partner)
                            <tbody wire:key="partner-{{ $partner['id'] }}" class="divide-y divide-gray-200 dark:divide-white/5!">
                                <tr
                                    class="cursor-pointer bg-gray-50/50 hover:bg-gray-100/50 dark:bg-white/5 dark:hover:bg-white/5!"
                                    x-data="{ loading: false }"
                                    @click="loading = true; $wire.togglePartnerLines({{ $partner['id'] }}).then(() => loading = false)"
                                >
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center">
                                            <svg x-show="!loading" @class(['h-4 w-4 transition-transform', 'rotate-90' => $this->isPartnerExpanded($partner['id'])]) fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            <x-filament::loading-indicator x-show="loading" x-cloak class="h-4 w-4" />
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $partner['partner_name'] }}</td>
                                    <td class="px-4 py-3"></td>
                                    @foreach (['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older'] as $column)
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <span @class(['text-red-600 dark:text-red-400' => $partner[$column] < 0, 'text-gray-900 dark:text-white' => $partner[$column] >= 0])>
                                                {{ \App\Support\OmrFormatter::formatNonZero($partner[$column]) }}
                                            </span>
                                        </td>
                                    @endforeach
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">
                                        <span @class(['text-red-600 dark:text-red-400' => $partner['total'] < 0, 'text-gray-900 dark:text-white' => $partner['total'] >= 0])>
                                            {{ \App\Support\OmrFormatter::format($partner['total']) }}
                                        </span>
                                    </td>
                                </tr>

                                @if($this->isPartnerExpanded($partner['id']))
                                    @foreach($this->getPartnerLines($partner['id']) as $line)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5!">
                                            <td class="px-4 py-2"></td>
                                            <td class="whitespace-nowrap px-4 py-2 pl-12 text-sm text-gray-600 dark:text-gray-400">{{ $line['move_name'] }}</td>
                                            <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ \App\Support\OmrFormatter::localizedDate($line['invoice_date']) }}</td>
                                            @foreach (['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older'] as $column)
                                                <td class="whitespace-nowrap px-4 py-2 text-right text-sm">
                                                    <span @class(['text-red-600 dark:text-red-400' => $line[$column] < 0, 'text-gray-600 dark:text-gray-400' => $line[$column] >= 0])>
                                                        {{ \App\Support\OmrFormatter::formatNonZero($line[$column]) }}
                                                    </span>
                                                </td>
                                            @endforeach
                                            <td class="px-4 py-2"></td>
                                        </tr>
                                    @endforeach
                                @endif

                                @foreach (['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older', 'total'] as $column)
                                    @php $totals[$column] += $partner[$column]; @endphp
                                @endforeach
                            </tbody>
                        @endforeach

                        <tbody>
                            <tr class="border-t-2 border-gray-300 bg-gray-100/80 font-semibold dark:border-white/5! dark:bg-white/5">
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ __("accounting-extensions::reporting.{$totalLabelKey}") }}</td>
                                <td class="px-4 py-3"></td>
                                @foreach (['at_date', 'period_1', 'period_2', 'period_3', 'period_4', 'older'] as $column)
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <span @class(['text-red-600 dark:text-red-400' => $totals[$column] < 0, 'text-gray-900 dark:text-white' => $totals[$column] >= 0])>
                                            {{ \App\Support\OmrFormatter::formatNonZero($totals[$column]) }}
                                        </span>
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($totals['total']) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
