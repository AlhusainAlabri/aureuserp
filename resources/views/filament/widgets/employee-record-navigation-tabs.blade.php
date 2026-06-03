<x-filament-widgets::widget :loading="false" class="!p-0 bg-transparent shadow-none">
    @php
        $hasOverflow = count($overflowItems) > 0;
        $overflowActive = collect($overflowItems)->contains(fn (array $item): bool => (bool) ($item['isActive'] ?? false));
    @endphp

    @if (count($primaryItems) > 0 || $hasOverflow)
        <div class="w-full max-w-full">
            <nav class="flex w-full max-w-full items-center gap-1">
                <div @class([
                    'flex min-w-0 gap-1 overflow-x-auto rounded-xl bg-gray-950/5 p-1 dark:bg-white/5',
                    'flex-1' => $hasOverflow,
                    'mx-auto' => ! $hasOverflow,
                ])>
                    @foreach ($primaryItems as $item)
                        @continue($item['isHidden'] ?? false)

                        <a
                            href="{{ $item['url'] }}"
                            @class([
                                'record-navigation-tabs-item inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-white text-primary-600 shadow-sm dark:bg-white/10 dark:text-primary-400' => $item['isActive'],
                                'text-gray-600 hover:bg-white/60 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $item['isActive'],
                            ])
                        >
                            @if ($item['icon'])
                                <x-filament::icon
                                    :icon="$item['isActive'] && ($item['activeIcon'] ?? null) ? $item['activeIcon'] : $item['icon']"
                                    class="h-5 w-5 shrink-0"
                                />
                            @endif

                            <span>{{ $item['label'] }}</span>

                            @if ($item['badge'])
                                <span class="fi-badge fi-badge-xs fi-badge-primary">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if ($hasOverflow)
                    <x-filament::dropdown placement="bottom-end">
                        <x-slot name="trigger">
                            <button
                                type="button"
                                @class([
                                    'inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                                    'bg-white text-primary-600 shadow-sm dark:bg-white/10 dark:text-primary-400' => $overflowActive,
                                    'text-gray-600 hover:bg-gray-950/5 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $overflowActive,
                                ])
                            >
                                <x-filament::icon icon="heroicon-o-ellipsis-horizontal" class="h-5 w-5 shrink-0" />
                                <span>{{ __('hr-extensions::employee.navigation.more') }}</span>
                            </button>
                        </x-slot>

                        <x-filament::dropdown.list>
                            @foreach ($overflowItems as $item)
                                @continue($item['isHidden'] ?? false)

                                <x-filament::dropdown.list.item
                                    :href="$item['url']"
                                    :icon="$item['isActive'] && ($item['activeIcon'] ?? null) ? $item['activeIcon'] : ($item['icon'] ?? null)"
                                    :badge="$item['badge'] ?? null"
                                    :color="($item['isActive'] ?? false) ? 'primary' : 'gray'"
                                >
                                    {{ $item['label'] }}
                                </x-filament::dropdown.list.item>
                            @endforeach
                        </x-filament::dropdown.list>
                    </x-filament::dropdown>
                @endif
            </nav>
        </div>
    @endif
</x-filament-widgets::widget>
