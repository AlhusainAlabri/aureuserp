@php
    $plugin = \Webkul\FullCalendar\FullCalendarPlugin::get();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex justify-end flex-1 mb-4">
            <x-filament::actions
                :actions="$this->getCachedHeaderActions()"
                class="shrink-0"
            />
        </div>

        @if ($this->showUnconfirmedMeetings)
            <div
                class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900/40"
                wire:key="meeting-calendar-status-legend"
            >
                <span class="font-medium text-gray-700 dark:text-gray-200">
                    {{ __('meetings::meetings.calendar.status_legend_title') }}
                </span>

                @foreach ($this->getStatusLegendItems() as $item)
                    <span class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-300">
                        <span
                            @class([
                                'inline-block h-3 w-5 rounded-sm border-2',
                                'border-dashed' => $item['dashed'],
                            ])
                            style="background-color: {{ $item['background'] }}; border-color: {{ $item['border'] }};"
                        ></span>
                        {{ $item['label'] }}
                    </span>
                @endforeach
            </div>
        @endif

        <div
            class="full-calendar"
            wire:ignore
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('full-calendar', 'full-calendar') }}"
            x-data="fullCalendar({
                locale: @js($plugin->getLocale()),
                plugins: @js($plugin->getPlugins()),
                timeZone: @js($plugin->getTimezone()),
                config: @js($this->getConfig()),
                editable: @json($plugin->isEditable()),
                selectable: @json($plugin->isSelectable()),
                eventClassNames: {!! htmlspecialchars($this->eventClassNames(), ENT_COMPAT) !!},
                eventContent: {!! htmlspecialchars($this->eventContent(), ENT_COMPAT) !!},
                eventDidMount: {!! htmlspecialchars($this->eventDidMount(), ENT_COMPAT) !!},
                eventWillUnmount: {!! htmlspecialchars($this->eventWillUnmount(), ENT_COMPAT) !!},
            })"
        ></div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
