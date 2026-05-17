<x-filament-panels::page>
    <div x-data="myNotes()" class="space-y-6">
        {{-- Toolbar --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-2">
                <x-filament::dropdown>
                    <x-slot name="trigger">
                        <x-filament::button icon="heroicon-m-plus">
                            {{ __('my-notes::notes.new_note') }}
                        </x-filament::button>
                    </x-slot>

                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item wire:click="createNote('text')" icon="heroicon-m-document-text">
                            {{ __('my-notes::notes.text_note') }}
                        </x-filament::dropdown.list.item>
                        <x-filament::dropdown.list.item wire:click="createNote('checklist')" icon="heroicon-m-check-circle">
                            {{ __('my-notes::notes.checklist') }}
                        </x-filament::dropdown.list.item>
                        <x-filament::dropdown.list.item wire:click="createNote('reminder')" icon="heroicon-m-bell">
                            {{ __('my-notes::notes.reminder') }}
                        </x-filament::dropdown.list.item>
                        <x-filament::dropdown.list.item wire:click="createNote('voice')" icon="heroicon-m-microphone">
                            {{ __('my-notes::notes.voice_memo') }}
                        </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>

            <div class="flex-1 max-w-md">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        :placeholder="__('my-notes::notes.search_placeholder')"
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <x-filament::button.group>
                    <x-filament::button size="sm" :color="$activeFilter === 'all' ? 'primary' : 'gray'" wire:click="$set('activeFilter', 'all')">
                        {{ __('my-notes::notes.all_notes') }}
                    </x-filament::button>
                    <x-filament::button size="sm" :color="$activeFilter === 'text' ? 'primary' : 'gray'" wire:click="$set('activeFilter', 'text')">
                        {{ __('my-notes::notes.text_note') }}
                    </x-filament::button>
                    <x-filament::button size="sm" :color="$activeFilter === 'checklist' ? 'primary' : 'gray'" wire:click="$set('activeFilter', 'checklist')">
                        {{ __('my-notes::notes.checklist') }}
                    </x-filament::button>
                    <x-filament::button size="sm" :color="$activeFilter === 'reminder' ? 'primary' : 'gray'" wire:click="$set('activeFilter', 'reminder')">
                        {{ __('my-notes::notes.reminder') }}
                    </x-filament::button>
                    <x-filament::button size="sm" :color="$activeFilter === 'pinned' ? 'primary' : 'gray'" wire:click="$set('activeFilter', 'pinned')">
                        {{ __('my-notes::notes.pinned') }}
                    </x-filament::button>
                    <x-filament::button size="sm" :color="$activeFilter === 'archived' ? 'primary' : 'gray'" wire:click="$set('activeFilter', 'archived')">
                        {{ __('my-notes::notes.archived') }}
                    </x-filament::button>
                </x-filament::button.group>

                <x-filament::button.group>
                    <x-filament::button size="sm" :color="$viewMode === 'grid' ? 'primary' : 'gray'" wire:click="$set('viewMode', 'grid')" icon="heroicon-m-squares-2x2">
                    </x-filament::button>
                    <x-filament::button size="sm" :color="$viewMode === 'list' ? 'primary' : 'gray'" wire:click="$set('viewMode', 'list')" icon="heroicon-m-list-bullet">
                    </x-filament::button>
                </x-filament::button.group>
            </div>
        </div>

        {{-- Pinned Notes --}}
        @if($this->pinnedNotes->isNotEmpty() && $activeFilter !== 'archived')
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-1">
                    <x-heroicon-m-map-pin class="w-4 h-4" />
                    {{ __('my-notes::notes.pinned') }}
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($this->pinnedNotes as $note)
                        @include('my-notes::partials.note-card', ['note' => $note])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Main Content --}}
        @if($this->getNotesProperty()->count() === 0)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 mb-6 text-gray-300 dark:text-gray-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ __('my-notes::notes.empty_title') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('my-notes::notes.empty_subtitle') }}</p>
                <x-filament::button wire:click="createNote('text')">
                    {{ __('my-notes::notes.create_first_note') }}
                </x-filament::button>
            </div>
        @else
            @if($viewMode === 'grid')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($this->unpinnedNotes as $note)
                        @include('my-notes::partials.note-card', ['note' => $note])
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('my-notes::notes.title') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('my-notes::notes.type') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('my-notes::notes.tags') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('my-notes::notes.reminder_at') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('my-notes::notes.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($this->unpinnedNotes as $note)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer" wire:click="editNote('{{ $note->ulid }}')">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $note->color_hex }}"></div>
                                            <span class="font-medium">{{ $note->auto_title }}</span>
                                            @if($note->is_pinned)
                                                <x-heroicon-m-map-pin class="w-4 h-4 text-amber-500" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 capitalize">{{ $note->type }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($note->tags ?? [] as $tag)
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $note->reminder_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1" onclick="event.stopPropagation()">
                                            <x-filament::icon-button wire:click="togglePin('{{ $note->ulid }}')" icon="heroicon-m-map-pin" color="gray" size="sm" />
                                            <x-filament::icon-button wire:click="toggleArchive('{{ $note->ulid }}')" icon="heroicon-m-archive-box" color="gray" size="sm" />
                                            <x-filament::icon-button wire:click="deleteNote('{{ $note->ulid }}')" icon="heroicon-m-trash" color="danger" size="sm" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>

    {{-- Slide-over --}}
    <x-filament::modal
        id="note-slide-over"
        :slide-over="true"
        width="md"
        wire:model="showSlideOver"
    >
        <x-slot name="heading">
            {{ $editingNoteUlid ? __('my-notes::notes.edit_note') : __('my-notes::notes.new_note') }}
        </x-slot>

        {{ $this->form }}

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="saveNote" color="primary">
                        {{ __('my-notes::notes.save') }}
                    </x-filament::button>
                    <x-filament::button wire:click="closeSlideOver" color="gray">
                        {{ __('my-notes::notes.cancel') }}
                    </x-filament::button>
                </div>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
