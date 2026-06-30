<x-filament-panels::page>
    @php
        $allItems = $this->getAllModuleItems();
        $moduleItems = $this->getVisibleModuleItems();
        $hiddenCount = $this->getHiddenItemsCount();
    @endphp

    <div
        class="module-launcher mx-auto w-full max-w-7xl"
        x-data="{
            filter: '',
            matches(label) {
                if (! this.filter.trim()) {
                    return true;
                }

                return label.toLowerCase().includes(this.filter.trim().toLowerCase());
            },
        }"
        x-on:module-launcher-filter.window="filter = $event.detail.query"
    >
        <header class="module-launcher__header mb-8 flex flex-col items-center gap-4 text-center">
            <img
                src="{{ asset('images/logo_2.png') }}"
                alt="{{ brand_name() }}"
                class="module-launcher__logo"
                decoding="async"
                fetchpriority="high"
            />

            <div class="module-launcher__intro max-w-2xl">
                <h1 class="text-lg font-semibold tracking-tight text-gray-700 sm:text-xl dark:text-gray-300">
                    {{ $this->getTitle() }}
                </h1>
            </div>
        </header>

        <div class="module-launcher__toolbar mx-auto mb-3 flex w-full max-w-3xl items-start justify-between gap-3">
            <div class="module-launcher__search min-w-0 flex-1">
                @livewire(\App\Livewire\ModuleLauncherGlobalSearch::class)
            </div>

            @if ($allItems->isNotEmpty())
                <div class="module-launcher__actions shrink-0 pt-0.5">
                    <x-filament::actions :actions="$this->getCachedHeaderActions()" />
                </div>
            @endif
        </div>

        @if ($allItems->isNotEmpty())
            <p class="module-launcher__meta mx-auto mb-6 max-w-3xl text-center text-xs text-gray-500 dark:text-gray-400">
                @if ($hiddenCount > 0)
                    {{ trans_choice('module-launcher.customize.hidden_count', $hiddenCount, ['count' => $hiddenCount]) }}
                @else
                    {{ __('module-launcher.customize.all_visible') }}
                @endif
            </p>
        @endif

        @if ($allItems->isEmpty())
            <div class="module-launcher__empty rounded-xl border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="text-lg font-medium text-gray-950 dark:text-white">
                    {{ __('module-launcher.empty.title') }}
                </p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('module-launcher.empty.description') }}
                </p>
            </div>
        @elseif ($moduleItems->isEmpty())
            <div class="module-launcher__empty rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-600 dark:bg-gray-900">
                <p class="text-lg font-medium text-gray-950 dark:text-white">
                    {{ __('module-launcher.customize.all_hidden_title') }}
                </p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('module-launcher.customize.all_hidden_description') }}
                </p>
            </div>
        @else
            <div class="module-launcher__grid grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($moduleItems as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @if ($item['opensInNewTab']) target="_blank" rel="noopener noreferrer" @endif
                        x-show="matches(@js($item['label']))"
                        x-cloak
                        @class([
                            'module-launcher__card group flex flex-col items-center justify-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-4 text-center shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-600',
                            'module-launcher__card--shortcut' => $item['type'] === 'shortcut',
                        ])
                    >
                        <span @class([
                            'module-launcher__icon flex h-16 w-16 items-center justify-center rounded-full',
                            match ($item['color']) {
                                'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
                                'info'    => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
                                'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                                'warning' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
                                'danger'  => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
                                default   => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                            },
                        ])>
                            @if (str_starts_with($item['icon'], 'heroicon-'))
                                <x-filament::icon
                                    :icon="$item['icon']"
                                    class="h-8 w-8"
                                />
                            @else
                                <x-filament::icon
                                    :icon="$item['icon']"
                                    class="h-10 w-10"
                                />
                            @endif
                        </span>

                        <span class="module-launcher__label line-clamp-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                            {{ $item['label'] }}
                        </span>

                        @if ($item['type'] === 'shortcut' && $item['opensInNewTab'])
                            <span class="sr-only">{{ __('module-launcher.opens_in_new_tab') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
