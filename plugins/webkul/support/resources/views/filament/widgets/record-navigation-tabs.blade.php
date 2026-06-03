<x-filament-widgets::widget :loading="false" class="!p-0 bg-transparent shadow-none">
    @if (count($navigationItems) > 0)
        <nav
            class="record-navigation-tabs mx-auto flex gap-1 rounded-xl bg-gray-950/5 p-1 dark:bg-white/5"
            style="width: max-content;"
        >
            @foreach ($navigationItems as $item)
                @continue($item['isHidden'] ?? false)

                <a
                    href="{{ $item['url'] }}"
                    @class([
                        'record-navigation-tabs-item inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
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
        </nav>
    @endif
</x-filament-widgets::widget>
