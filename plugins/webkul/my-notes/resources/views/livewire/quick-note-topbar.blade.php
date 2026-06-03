@php
    $noteTypes = [
        'text'      => 'heroicon-m-document-text',
        'checklist' => 'heroicon-m-check-circle',
        'reminder'  => 'heroicon-m-bell',
        'voice'     => 'heroicon-m-microphone',
    ];
@endphp

<div class="fi-quick-note-topbar">
    <x-filament::dropdown
        placement="bottom-end"
        teleport
        width="xs"
    >
        <x-slot name="trigger">
            <x-filament::icon-button
                color="gray"
                icon="heroicon-o-document-plus"
                :label="__('my-notes::notes.topbar.quick_add')"
                size="lg"
            />
        </x-slot>

        <div class="w-72 max-w-[calc(100vw-1.5rem)] p-1 sm:w-80">
            <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('my-notes::notes.topbar.heading') }}
            </p>

            <form wire:submit="saveQuickNote" class="space-y-2 px-2 pb-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="quickBody"
                        :placeholder="__('my-notes::notes.topbar.capture_placeholder')"
                        autocomplete="off"
                    />
                </x-filament::input.wrapper>

                <x-filament::button
                    type="submit"
                    size="sm"
                    class="w-full"
                    icon="heroicon-m-bolt"
                    wire:loading.attr="disabled"
                    wire:target="saveQuickNote"
                >
                    <span wire:loading.remove wire:target="saveQuickNote">
                        {{ __('my-notes::notes.topbar.capture') }}
                    </span>
                    <span wire:loading wire:target="saveQuickNote">
                        {{ __('my-notes::notes.actions.saving') }}
                    </span>
                </x-filament::button>
            </form>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item
                    :href="$this->notesIndexUrl()"
                    icon="heroicon-m-squares-2x2"
                    tag="a"
                >
                    {{ __('my-notes::notes.topbar.open_notes') }}
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>

            <div class="border-t border-gray-100 px-2 py-2 dark:border-white/10">
                <p class="mb-1.5 px-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('my-notes::notes.topbar.new_by_type') }}
                </p>
                <div class="grid grid-cols-2 gap-1">
                    @foreach($noteTypes as $type => $icon)
                        <a
                            href="{{ $this->createTypeUrl($type) }}"
                            class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5"
                        >
                            <x-filament::icon :icon="$icon" class="h-4 w-4 shrink-0 text-gray-400" />
                            <span class="truncate">{{ __('my-notes::notes.types.'.$type) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::dropdown>
</div>
