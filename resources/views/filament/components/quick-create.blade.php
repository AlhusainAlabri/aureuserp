@php
    use App\Support\Navigation\QuickCreateMenu;

    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $groups = QuickCreateMenu::groups();
@endphp

@if ($groups !== [])
    <div
        class="relative flex items-center"
        x-data="{ open: false }"
        x-on:keydown.escape.window="open = false"
        x-on:click.outside="open = false"
    >
        {{-- Trigger button --}}
        <button
            type="button"
            x-on:click.stop="open = !open"
            class="fi-quick-create-btn inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition
                   bg-primary-600 text-white hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-1
                   dark:bg-primary-500 dark:hover:bg-primary-400"
            aria-haspopup="true"
            x-bind:aria-expanded="open"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
            </svg>
            <span class="hidden sm:inline">{{ __('admin.quick_create.button') }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                class="h-3.5 w-3.5 shrink-0 opacity-75 transition-transform duration-150"
                :class="{ 'rotate-180': open }"
                aria-hidden="true"
            >
                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        {{-- Dropdown panel --}}
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            @class([
                'absolute top-full z-50 mt-2 w-[min(100vw-1.5rem,56rem)] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900',
                'right-0 origin-top-right' => ! $isRtl,
                'left-0 origin-top-left' => $isRtl,
            ])
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    {{ __('admin.quick_create.heading') }}
                </p>
                <button
                    type="button"
                    x-on:click="open = false"
                    class="rounded-md p-0.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                    aria-label="{{ __('admin.quick_create.close') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>

            {{-- Groups grid --}}
            <div class="max-h-[min(32rem,calc(100vh-7.5rem))] overflow-y-auto p-3 sm:p-4">
                <div class="grid grid-cols-1 gap-x-4 gap-y-4 min-[480px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($groups as $group)
                        <div class="min-w-0">
                            {{-- Group header --}}
                            <div class="mb-1.5 flex items-start gap-1.5 rounded-md px-2 py-1">
                                <x-filament::icon
                                    :icon="$group['icon']"
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary-500 dark:text-primary-400"
                                />
                                <span class="text-xs font-semibold leading-snug text-gray-500 dark:text-gray-400">
                                    {{ $group['label'] }}
                                </span>
                            </div>

                            {{-- Group items --}}
                            @foreach ($group['items'] as $item)
                                <a
                                    href="{{ $item['url'] }}"
                                    x-on:click="open = false"
                                    class="group flex items-start gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 transition
                                           hover:bg-primary-50 hover:text-primary-700
                                           dark:text-gray-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
                                >
                                    <x-filament::icon
                                        :icon="$item['icon']"
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-gray-400 transition group-hover:text-primary-600 dark:text-gray-500 dark:group-hover:text-primary-400"
                                    />
                                    <span class="min-w-0 flex-1 break-words leading-snug">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
