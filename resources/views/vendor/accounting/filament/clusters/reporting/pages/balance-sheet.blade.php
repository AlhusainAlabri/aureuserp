<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section>
            @php
                $data = $this->balanceSheetData;
            @endphp

            <x-slot name="heading">
                {{ __('accounting-extensions::reporting.balance-sheet.heading', [
                    'date' => \Carbon\Carbon::parse($data['date'])->locale(app()->getLocale())->translatedFormat('j F Y'),
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
                                {{ __('accounting-extensions::reporting.balance') }}
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

                            @foreach($section['subsections'] as $subsection)
                                @php
                                    $hasAccounts = count($subsection['accounts']) > 0;
                                    $showSubsection = $hasAccounts || ! isset($subsection['show_if_empty']) || $subsection['show_if_empty'];
                                @endphp

                                @if($showSubsection)
                                    <tr class="bg-gray-50/50 dark:bg-gray-900/50">
                                        <td class="px-4 py-2 font-semibold text-gray-900 dark:text-white" style="padding-left: 2rem;">
                                            {{ $subsection['title'] }}
                                        </td>
                                        <td class="px-4 py-2 text-right"></td>
                                    </tr>

                                    @if($hasAccounts)
                                        @foreach($subsection['accounts'] as $account)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5!">
                                                <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400" style="padding-left: 4rem;">
                                                    {{ $account['code'] ? $account['code'] . ' - ' : '' }}{{ $account['name'] }}
                                                </td>
                                                <td class="px-4 py-2 text-right text-sm text-gray-900 dark:text-gray-100!">
                                                    {{ \App\Support\OmrFormatter::format($account['balance']) }}
                                                </td>
                                            </tr>
                                        @endforeach

                                        <tr class="border-t border-gray-200 bg-gray-50/50 font-semibold dark:border-gray-700 dark:bg-gray-900/50">
                                            <td class="px-4 py-2 text-gray-900 dark:text-white" style="padding-left: 2rem;">
                                                {{ $subsection['total_label'] }}
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-white">
                                                {{ \App\Support\OmrFormatter::format($subsection['total']) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endif
                            @endforeach

                            <tr class="border-t-2 border-gray-300 bg-gray-100/80 font-bold dark:border-gray-600 dark:bg-white/5">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ $section['total_label'] }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ \App\Support\OmrFormatter::format($section['total']) }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="border-t-4 border-gray-400 bg-gray-200/80 text-base font-bold dark:border-white/5! dark:bg-gray-700/80">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                {{ $data['grand_total_label'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ \App\Support\OmrFormatter::format($data['grand_total']) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
