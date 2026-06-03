@php
    use Webkul\MyNotes\Enums\NoteBoardStatus;
    use Webkul\MyNotes\Support\NoteDateFormatter;

    $isOverdue = $note->isOverdue();
    $progress  = $note->isChecklist() ? $note->getChecklistProgress() : null;
    $isList    = ($viewMode ?? 'grid') === 'list';
    $isGrid    = ! $isList;
    $compact   = $compact ?? false;
    $authorName = $note->user?->name ?? auth()->user()?->name ?? '';

    $typeColor = match ($note->type) {
        'checklist' => 'success',
        'reminder'  => 'warning',
        'voice'     => 'info',
        default     => 'gray',
    };
    $typeIcon = match ($note->type) {
        'checklist' => 'heroicon-m-check-circle',
        'reminder'  => 'heroicon-m-bell',
        'voice'     => 'heroicon-m-microphone',
        default     => 'heroicon-m-document-text',
    };
@endphp

<article
    wire:key="note-{{ $note->ulid }}"
    @class([
        'my-notes-sticky-card group relative flex flex-col overflow-hidden rounded-xl shadow-sm',
        'my-notes-sticky-card--grid' => $isGrid,
        'my-notes-sticky-card--list' => $isList,
        'my-notes-sticky-card--compact' => $compact,
        'ring-2 ring-danger-300 dark:ring-danger-800' => $isOverdue && $isGrid,
        'border border-danger-300 ring-2 ring-danger-200 dark:ring-danger-900' => $isOverdue && $isList,
        'border border-gray-200/80 dark:border-gray-600' => $isList && ! $isOverdue,
        'opacity-70' => $note->is_archived,
        'flex-row items-stretch' => $isList,
    ])
    style="background-color: {{ $note->sticky_background }}; --sticky-bg: {{ $note->sticky_background }}; --sticky-bg-dark: {{ $note->sticky_background_dark }}; --sticky-rotate: {{ $compact && $isGrid ? 0 : $note->sticky_rotation }}deg;"
>
    @if($isGrid && ! $compact)
        <span class="my-notes-sticky-card__tape" aria-hidden="true"></span>
    @endif

    @if($isList)
        <div class="w-1.5 shrink-0 rounded-s-xl" style="background-color: {{ $note->color_hex }}"></div>
    @endif

    <div @class(['flex min-w-0 flex-1 flex-col', 'md:flex-row md:items-start md:gap-4' => $isList])>
        <button
            type="button"
            wire:click="editNote('{{ $note->ulid }}')"
            @class([
                'min-w-0 flex-1 bg-transparent text-left hover:bg-black/5 dark:hover:bg-white/5',
                'p-4 pt-5' => $isGrid && ! $compact,
                'p-4 pt-4' => $isGrid && $compact,
                'p-4' => $isList,
                'md:flex-shrink' => $isList,
            ])
        >
            <div class="flex items-start gap-2">
                <x-filament::icon
                    :icon="$typeIcon"
                    @class([
                        'mt-0.5 h-4 w-4 shrink-0 text-gray-500 dark:text-gray-400',
                        'text-success-600 dark:text-success-400' => $note->type === 'checklist',
                        'text-warning-600 dark:text-warning-400' => $note->type === 'reminder',
                        'text-info-600 dark:text-info-400' => $note->type === 'voice',
                    ])
                />

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <h4 class="line-clamp-2 text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $note->auto_title }}
                        </h4>
                        @if($note->is_pinned)
                            <x-heroicon-m-map-pin class="mt-0.5 h-4 w-4 shrink-0 text-warning-500" />
                        @endif
                    </div>

                    @if($isList || $note->is_archived || $note->reminder_status)
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            @if($isList)
                                <x-filament::badge :color="$typeColor" :icon="$typeIcon" size="sm">
                                    {{ __('my-notes::notes.types.'.$note->type) }}
                                </x-filament::badge>
                            @endif

                            @if($note->is_archived)
                                <x-filament::badge color="gray" size="sm">
                                    {{ __('my-notes::notes.filters.archived') }}
                                </x-filament::badge>
                            @endif

                            @if($note->reminder_status)
                                <x-filament::badge
                                    :color="$note->reminder_status === 'overdue' ? 'danger' : ($note->reminder_status === 'sent' ? 'success' : 'info')"
                                    size="sm"
                                >
                                    {{ __('my-notes::notes.reminder_status.'.$note->reminder_status) }}
                                </x-filament::badge>
                            @endif
                        </div>
                    @endif

                    <div class="mt-3 space-y-2">
                        @if($note->isText() && $note->body)
                            <p class="line-clamp-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ strip_tags($note->body) }}
                            </p>
                        @endif

                        @if($note->isReminder() && $note->reminder_at)
                            <div @class([
                                'flex items-center gap-1.5 text-sm',
                                'font-medium text-danger-600 dark:text-danger-400' => $isOverdue,
                                'text-gray-500 dark:text-gray-400' => ! $isOverdue,
                            ])>
                                <x-heroicon-m-bell class="h-4 w-4 shrink-0" />
                                {{ NoteDateFormatter::formatDateTime($note->reminder_at) }}
                            </div>
                        @endif

                        @if($note->isVoice())
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <x-heroicon-m-microphone class="h-4 w-4 shrink-0" />
                                @if($note->audio_duration_seconds)
                                    <span class="font-mono text-xs">{{ gmdate('i:s', $note->audio_duration_seconds) }}</span>
                                @endif
                            </div>
                            @if($note->audio_url)
                                <audio controls preload="none" class="mt-1 w-full" onclick="event.stopPropagation()">
                                    <source src="{{ $note->audio_url }}">
                                </audio>
                            @endif
                            @if($note->audio_transcription)
                                <p class="line-clamp-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $note->audio_transcription }}
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </button>

        @if($note->isChecklist())
            <div @class(['p-4 pt-0' => ! $isList, 'min-w-60 border-t border-gray-100 p-4 dark:border-gray-800 md:border-s-0 md:border-t-0 md:border-e' => $isList])>
                <div class="space-y-2">
                    @forelse($note->checklistItems->take($isList ? 6 : 4) as $item)
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm" onclick="event.stopPropagation()">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                                @checked($item->is_checked)
                                wire:click="toggleChecklistItem({{ $item->id }})"
                            />
                            <span @class([
                                'min-w-0 truncate',
                                'text-gray-400 line-through dark:text-gray-500' => $item->is_checked,
                                'text-gray-700 dark:text-gray-200' => ! $item->is_checked,
                            ])>{{ $item->content }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500">
                            {{ __('my-notes::notes.empty_state.no_checklist_items') }}
                        </p>
                    @endforelse

                    @php $shown = $isList ? 6 : 4; @endphp
                    @if($note->checklistItems->count() > $shown)
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ __('my-notes::notes.more_items', ['count' => $note->checklistItems->count() - $shown]) }}
                        </p>
                    @endif
                </div>

                @if($progress && $progress['total'] > 0)
                    <div class="mt-3 space-y-1">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $progress['done'] }}/{{ $progress['total'] }}</span>
                            <span>{{ $progress['percent'] }}%</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200/80 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full bg-success-500 transition-all duration-300"
                                style="width: {{ $progress['percent'] }}%"
                            ></div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if(! empty($note->tags) || $note->hasLinkedModule())
        <div class="flex flex-wrap items-center gap-1.5 border-t border-gray-900/5 px-4 py-2 dark:border-white/10">
            @foreach(array_slice($note->tags ?? [], 0, 3) as $tag)
                <x-filament::badge color="gray" size="sm">{{ $tag }}</x-filament::badge>
            @endforeach
            @if(count($note->tags ?? []) > 3)
                <x-filament::badge color="gray" size="sm">
                    {{ __('my-notes::notes.more_items', ['count' => count($note->tags) - 3]) }}
                </x-filament::badge>
            @endif

            @if($note->hasLinkedModule())
                <div class="ms-auto flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <x-heroicon-m-link class="h-3 w-3" />
                    @if($note->meeting_id)
                        {{ $note->meeting?->meeting_number ?? $note->meeting?->title ?? '#' }}
                    @elseif($note->project_id)
                        {{ $note->project?->name ?? '#' }}
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if(filled($authorName) || $note->updated_at)
        <div class="flex items-center justify-between gap-2 border-t border-gray-900/5 px-4 py-2 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
            @if(filled($authorName))
                <span class="flex min-w-0 items-center gap-1 truncate">
                    <x-heroicon-m-user class="h-3.5 w-3.5 shrink-0" />
                    <span class="truncate">{{ $authorName }}</span>
                </span>
            @else
                <span></span>
            @endif

            @if($note->updated_at)
                <span class="flex shrink-0 items-center gap-1">
                    <x-heroicon-m-clock class="h-3.5 w-3.5 shrink-0" />
                    {{ __('my-notes::notes.card.updated', ['date' => NoteDateFormatter::formatDate($note->updated_at)]) }}
                </span>
            @endif
        </div>
    @endif

    <div
        @class([
            'my-notes-sticky-card__actions flex items-center justify-between gap-2 border-t border-gray-900/5 px-3 py-2 dark:border-white/10',
            'bg-black/[0.04] dark:bg-black/20' => $isGrid,
            'bg-gray-50/70 dark:bg-gray-950/50' => $isList,
        ])
        onclick="event.stopPropagation()"
    >
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1">
            @unless($compact)
                <label class="sr-only" for="board-status-{{ $note->ulid }}">
                    {{ __('my-notes::notes.form.fields.board_status') }}
                </label>
                <select
                    id="board-status-{{ $note->ulid }}"
                    wire:change="moveNoteToBoard('{{ $note->ulid }}', $event.target.value)"
                    class="max-w-[7rem] rounded-md border-gray-200/80 bg-white/80 py-0.5 text-xs text-gray-700 dark:border-gray-600 dark:bg-gray-900/80 dark:text-gray-200"
                >
                    @foreach(NoteBoardStatus::options() as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(
                                NoteBoardStatus::tryFromValue(
                                    $note->board_status instanceof NoteBoardStatus
                                        ? $note->board_status->value
                                        : (string) $note->board_status
                                )->value === $value
                            )
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            @endunless

            <x-filament::icon-button
                wire:click="togglePin('{{ $note->ulid }}')"
                icon="heroicon-m-map-pin"
                :color="$note->is_pinned ? 'warning' : 'gray'"
                size="sm"
                :tooltip="$note->is_pinned ? __('my-notes::notes.actions.unpin') : __('my-notes::notes.actions.pin')"
                :label="$note->is_pinned ? __('my-notes::notes.actions.unpin') : __('my-notes::notes.actions.pin')"
            />
            <x-filament::icon-button
                wire:click="toggleArchive('{{ $note->ulid }}')"
                icon="{{ $note->is_archived ? 'heroicon-m-arrow-up-tray' : 'heroicon-m-archive-box' }}"
                :color="$note->is_archived ? 'primary' : 'gray'"
                size="sm"
                :tooltip="$note->is_archived ? __('my-notes::notes.actions.unarchive') : __('my-notes::notes.actions.archive')"
                :label="$note->is_archived ? __('my-notes::notes.actions.unarchive') : __('my-notes::notes.actions.archive')"
            />
            <x-filament::icon-button
                wire:click="editNote('{{ $note->ulid }}')"
                icon="heroicon-m-pencil-square"
                color="gray"
                size="sm"
                :label="__('my-notes::notes.actions.edit')"
            />
        </div>

        <x-filament::icon-button
            wire:click="deleteNote('{{ $note->ulid }}')"
            wire:confirm="{{ __('my-notes::notes.actions.confirm_delete') }}"
            icon="heroicon-m-trash"
            color="danger"
            size="sm"
            :label="__('my-notes::notes.actions.delete')"
        />
    </div>
</article>
