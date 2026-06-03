@php
    use Webkul\MyNotes\Enums\NoteBoardStatus;
    use Webkul\MyNotes\Support\NoteDateFormatter;

    $scopeFilters = [
        'all'      => 'heroicon-m-rectangle-stack',
        'pinned'   => 'heroicon-m-bookmark',
        'archived' => 'heroicon-m-archive-box',
    ];
@endphp

<x-filament-panels::page>
    <div class="my-notes-toolbar">
        <div class="min-w-0">
            <x-filament::section class="!p-4 md:!p-5">
                <x-slot name="heading">
                    {{ __('my-notes::notes.toolbar.browse_heading') }}
                </x-slot>

                <div class="flex flex-col gap-2">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            :placeholder="__('my-notes::notes.toolbar.search_placeholder')"
                        />
                    </x-filament::input.wrapper>

                    <div class="flex w-full flex-wrap items-center gap-1.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button size="sm" color="gray" icon="heroicon-m-arrows-up-down">
                                        {{ __('my-notes::notes.toolbar.sort') }}
                                    </x-filament::button>
                                </x-slot>
                                <x-filament::dropdown.list>
                                    @foreach(['newest' => __('my-notes::notes.sort.newest'), 'oldest' => __('my-notes::notes.sort.oldest'), 'a-z' => __('my-notes::notes.sort.title'), 'reminder' => __('my-notes::notes.sort.reminder'), 'pinned-first' => __('my-notes::notes.sort.pinned_first')] as $value => $label)
                                        <x-filament::dropdown.list.item wire:click="$set('sortBy', '{{ $value }}')">
                                            {{ $label }}
                                        </x-filament::dropdown.list.item>
                                    @endforeach
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button size="sm" color="gray" icon="heroicon-m-squares-2x2">
                                        {{ __('my-notes::notes.toolbar.view') }}
                                    </x-filament::button>
                                </x-slot>
                                <x-filament::dropdown.list>
                                    @foreach(['grid', 'list', 'board', 'calendar'] as $mode)
                                        <x-filament::dropdown.list.item wire:click="$set('viewMode', '{{ $mode }}')">
                                            {{ __('my-notes::notes.view_modes.'.$mode) }}
                                        </x-filament::dropdown.list.item>
                                    @endforeach
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button size="sm" color="gray" icon="heroicon-m-funnel">
                                        {{ __('my-notes::notes.toolbar.filter') }}
                                    </x-filament::button>
                                </x-slot>
                                <x-filament::dropdown.list>
                                    @foreach(['all', 'text', 'checklist', 'reminder', 'voice', 'pinned', 'archived'] as $filter)
                                        <x-filament::dropdown.list.item wire:click="$set('activeFilter', '{{ $filter }}')">
                                            {{ $filter === 'all' || $filter === 'pinned' || $filter === 'archived'
                                                ? __('my-notes::notes.filters.'.$filter)
                                                : __('my-notes::notes.types.'.$filter) }}
                                        </x-filament::dropdown.list.item>
                                    @endforeach
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>

                        <div class="my-notes-scope-filters ms-auto flex flex-wrap items-center gap-1.5 max-md:w-full max-md:justify-end rtl:ms-0 rtl:me-auto">
                            @foreach($scopeFilters as $scope => $icon)
                                <x-filament::button
                                    wire:click="$set('activeFilter', '{{ $scope }}')"
                                    size="sm"
                                    :color="$activeFilter === $scope ? 'primary' : 'gray'"
                                    :icon="$icon"
                                >
                                    {{ __('my-notes::notes.filters.'.$scope) }}
                                </x-filament::button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>

    <div wire:loading.delay wire:target="search,activeFilter,sortBy,viewMode" class="flex justify-center py-4">
        <x-filament::loading-indicator class="h-6 w-6 text-primary-600" />
    </div>

    <div wire:loading.remove wire:target="search,activeFilter,sortBy,viewMode">
        @if($this->notes->isEmpty())
            <x-filament::section>
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <x-heroicon-o-document-text class="mb-4 h-16 w-16 text-gray-300 dark:text-gray-600" />
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('my-notes::notes.empty_state.heading') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ filled($search) || $activeFilter !== 'all'
                            ? __('my-notes::notes.empty_state.filtered_description')
                            : __('my-notes::notes.empty_state.description') }}
                    </p>
                </div>
            </x-filament::section>
        @elseif($viewMode === 'board')
            <div class="my-notes-board-grid grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach(NoteBoardStatus::cases() as $boardStatus)
                    @include('my-notes::components.board-column', [
                        'status' => $boardStatus->value,
                        'notes' => $this->boardNotes[$boardStatus->value] ?? collect(),
                        'viewMode' => 'board',
                    ])
                @endforeach
            </div>
        @elseif($viewMode === 'calendar')
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                @forelse($this->calendarNotes as $date => $notes)
                    <x-filament::section>
                        <x-slot name="heading">
                            {{ NoteDateFormatter::formatDate($date) }}
                        </x-slot>
                        <div class="space-y-2">
                            @foreach($notes as $note)
                                <button
                                    type="button"
                                    wire:click="editNote('{{ $note->ulid }}')"
                                    class="flex w-full items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-start text-sm transition hover:border-primary-400 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-500"
                                >
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-gray-950 dark:text-white">
                                            {{ $note->auto_title }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ NoteDateFormatter::formatTime($note->reminder_at) }}
                                        </span>
                                    </span>
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $note->color_hex }}"></span>
                                </button>
                            @endforeach
                        </div>
                    </x-filament::section>
                @empty
                    <x-filament::section class="col-span-full">
                        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('my-notes::notes.calendar.empty') }}
                        </p>
                    </x-filament::section>
                @endforelse
            </div>
        @else
            <div class="my-notes-canvas rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-6">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('my-notes::notes.canvas.showing', ['count' => $this->notes->count()]) }}
                </p>

                @if($this->pinnedNotes->isNotEmpty())
                    <section class="mb-6 space-y-3">
                        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                            <x-heroicon-m-map-pin class="h-4 w-4 text-warning-500" />
                            {{ __('my-notes::notes.filters.pinned') }}
                            <x-filament::badge color="warning" size="sm">{{ $this->pinnedNotes->count() }}</x-filament::badge>
                        </div>
                        <div class="my-notes-pinned-strip -mx-1 flex gap-4 overflow-x-auto px-1 pb-2">
                            @foreach($this->pinnedNotes as $note)
                                <div class="w-64 shrink-0 sm:w-72">
                                    @include('my-notes::partials.note-card', ['note' => $note, 'viewMode' => 'grid'])
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($this->pinnedNotes->isNotEmpty() && $this->unpinnedNotes->isNotEmpty())
                    <div class="mb-4 flex items-center gap-3 text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                        {{ __('my-notes::notes.filters.other') }}
                        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                    </div>
                @endif

                <div @class([
                    'grid gap-5',
                    'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' => $viewMode === 'grid',
                    'grid-cols-1 gap-4' => $viewMode === 'list',
                ])>
                    @foreach($this->unpinnedNotes as $note)
                        @include('my-notes::partials.note-card', ['note' => $note, 'viewMode' => $viewMode])
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <x-filament::modal id="note-slide-over" :slide-over="true" width="lg">
        <x-slot name="heading">
            {{ $editingNoteUlid ? __('my-notes::notes.actions.edit_note') : __('my-notes::notes.actions.new_note') }}
        </x-slot>

        @if(($data['type'] ?? null) === 'reminder')
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach(['hour' => ['heroicon-m-clock', 'in_one_hour'], 'day' => ['heroicon-m-sun', 'tomorrow_9am'], 'monday' => ['heroicon-m-calendar', 'next_monday']] as $preset => [$icon, $key])
                    <x-filament::badge
                        wire:click="applyReminderPreset('{{ $preset }}')"
                        color="gray"
                        class="cursor-pointer hover:ring-2 hover:ring-primary-400"
                        :icon="$icon"
                    >
                        {{ __('my-notes::notes.reminder_presets.'.$key) }}
                    </x-filament::badge>
                @endforeach
            </div>
        @endif

        @if(($data['type'] ?? null) === 'voice')
            <div
                x-data="{
                    recorder: null, chunks: [], recording: false, error: '', duration: 0, timer: null,
                    async start() {
                        this.error = '';
                        if (! navigator.mediaDevices || ! window.MediaRecorder) {
                            this.error = @js(__('my-notes::notes.voice.not_supported')); return;
                        }
                        try {
                            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                            this.chunks = [];
                            this.recorder = new MediaRecorder(stream);
                            this.recorder.ondataavailable = (e) => this.chunks.push(e.data);
                            this.recorder.onstop = () => {
                                stream.getTracks().forEach(t => t.stop());
                                const file = new File([new Blob(this.chunks, { type: 'audio/webm' })], `note-${Date.now()}.webm`, { type: 'audio/webm' });
                                $wire.upload('data.audio_path', file, () => {}, () => {
                                    this.error = @js(__('my-notes::notes.voice.upload_failed'));
                                });
                            };
                            this.duration = 0;
                            this.timer = setInterval(() => this.duration++, 1000);
                            this.recording = true;
                            this.recorder.start();
                        } catch {
                            this.error = @js(__('my-notes::notes.voice.permission_denied'));
                        }
                    },
                    stop() {
                        if (! this.recorder || ! this.recording) return;
                        clearInterval(this.timer); this.recording = false; this.recorder.stop();
                    },
                    discard() {
                        clearInterval(this.timer);
                        if (this.recorder && this.recording) this.recorder.stop();
                        this.chunks = []; this.recording = false; this.duration = 0;
                    },
                    formatDuration(s) { return Math.floor(s / 60).toString().padStart(2,'0') + ':' + (s % 60).toString().padStart(2,'0'); }
                }"
                class="mb-4 rounded-xl border-2 border-dashed border-primary-200 bg-primary-50/50 p-5 dark:border-primary-800 dark:bg-primary-950/30"
            >
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('my-notes::notes.voice.record_hint') }}
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-filament::button x-show="! recording" type="button" size="sm" icon="heroicon-m-microphone" x-on:click="start">
                        {{ __('my-notes::notes.voice.record') }}
                    </x-filament::button>
                    <x-filament::button x-show="recording" type="button" size="sm" color="danger" icon="heroicon-m-stop" x-on:click="stop">
                        {{ __('my-notes::notes.voice.stop') }}
                    </x-filament::button>
                    <x-filament::button x-show="recording || duration > 0" type="button" size="sm" color="gray" x-on:click="discard">
                        {{ __('my-notes::notes.voice.discard') }}
                    </x-filament::button>
                    <div x-show="recording" class="flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-danger-500"></span>
                        <span class="font-mono text-sm font-medium text-danger-600 dark:text-danger-400" x-text="formatDuration(duration)"></span>
                    </div>
                </div>
                <p x-show="error" x-text="error" class="mt-2 text-sm text-danger-600 dark:text-danger-400"></p>
            </div>
        @endif

        {{ $this->form }}

        <x-slot name="footer">
            <div class="flex w-full items-center justify-end gap-3">
                <x-filament::button wire:click="closeSlideOver" color="gray">
                    {{ __('my-notes::notes.actions.cancel') }}
                </x-filament::button>
                <x-filament::button
                    wire:click="saveNote"
                    color="primary"
                    wire:loading.attr="disabled"
                    wire:target="saveNote"
                    icon="heroicon-m-check"
                >
                    <span wire:loading.remove wire:target="saveNote">{{ __('my-notes::notes.actions.save') }}</span>
                    <span wire:loading wire:target="saveNote">{{ __('my-notes::notes.actions.saving') }}</span>
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
