@php
    use App\Support\Navigation\QuickCreateMenu;

    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $groups = QuickCreateMenu::groups();
@endphp

@if ($groups !== [])
    <x-filament::dropdown
        :placement="$isRtl ? 'bottom-end' : 'bottom-start'"
        class="fi-quick-create-dropdown"
        flip
        max-height="32rem"
        shift
        teleport
        width="5xl"
    >
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-quick-create-btn inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition
                       bg-primary-600 text-white hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-1
                       dark:bg-primary-500 dark:hover:bg-primary-400"
                aria-haspopup="true"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                <span class="hidden sm:inline">{{ __('admin.quick_create.button') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 shrink-0 opacity-75" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>
        </x-slot>

        <div class="fi-quick-create-panel">
            <div class="fi-quick-create-panel__header">
                <p class="fi-quick-create-panel__title">
                    {{ __('admin.quick_create.heading') }}
                </p>
            </div>

            <div class="fi-quick-create-panel__body">
                <div class="fi-quick-create-grid">
                    @foreach ($groups as $group)
                        <div class="fi-quick-create-group">
                            <div class="fi-quick-create-group__header">
                                <x-filament::icon
                                    :icon="$group['icon']"
                                    class="fi-quick-create-group__icon"
                                />
                                <span class="fi-quick-create-group__label">
                                    {{ $group['label'] }}
                                </span>
                            </div>

                            @foreach ($group['items'] as $item)
                                <a href="{{ $item['url'] }}" class="fi-quick-create-link group">
                                    <x-filament::icon
                                        :icon="$item['icon']"
                                        class="fi-quick-create-link__icon"
                                    />
                                    <span class="fi-quick-create-link__label">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::dropdown>
@endif
