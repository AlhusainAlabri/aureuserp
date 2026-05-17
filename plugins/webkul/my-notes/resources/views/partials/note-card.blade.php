@php
$isOverdue = $note->isOverdue();
$progress = $note->isChecklist() ? $note->getChecklistProgress() : null;
@endphp

<div
    wire:click="editNote('{{ $note->ulid }}')"
    class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden cursor-pointer transition-all duration-150 hover:shadow-md {{ $note->is_archived ? 'opacity-50' : '' }} {{ $isOverdue ? 'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}"
>
    {{-- Color top border --}}
    <div class="h-1.5 w-full" style="background-color: {{ $note->color_hex }}"></div>

    <div class="p-4 space-y-3">
        {{-- Title --}}
        <h4 class="font-semibold text-sm text-gray-900 dark:text-white line-clamp-2">
            {{ $note->auto_title }}
        </h4>

        {{-- Content preview --}}
        @if($note->isText() && $note->body)
            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3">{{ strip_tags($note->body) }}</p>
        @endif

        @if($note->isChecklist() && $note->checklistItems->isNotEmpty())
            <div class="space-y-1">
                @foreach($note->checklistItems->take(3) as $item)
                    <div class="flex items-center gap-2 text-sm">
                        @if($item->is_checked)
                            <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 shrink-0" />
                            <span class="text-gray-400 dark:text-gray-500 line-through">{{ $item->content }}</span>
                        @else
                            <div class="w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-600 shrink-0"></div>
                            <span class="text-gray-700 dark:text-gray-200">{{ $item->content }}</span>
                        @endif
                    </div>
                @endforeach
                @if($note->checklistItems->count() > 3)
                    <p class="text-xs text-gray-400">+{{ $note->checklistItems->count() - 3 }} more</p>
                @endif
            </div>
            @if($progress && $progress['total'] > 0)
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                    <div class="bg-green-500 h-1 rounded-full transition-all" style="width: {{ $progress['percent'] }}%"></div>
                </div>
            @endif
        @endif

        @if($note->isReminder() && $note->reminder_at)
            <div class="flex items-center gap-1.5 text-sm {{ $isOverdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                <x-heroicon-m-bell class="w-4 h-4" />
                {{ $note->reminder_at->format('D, d M · h:i A') }}
                @if($isOverdue)
                    <span class="text-xs bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 px-1.5 py-0.5 rounded">{{ __('my-notes::notes.overdue') }}</span>
                @endif
            </div>
        @endif

        @if($note->isVoice())
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <x-heroicon-m-microphone class="w-4 h-4" />
                {{ __('my-notes::notes.voice_memo') }}
                @if($note->audio_duration_seconds)
                    <span>{{ gmdate('i:s', $note->audio_duration_seconds) }}</span>
                @endif
            </div>
        @endif

        {{-- Tags --}}
        @if(!empty($note->tags))
            <div class="flex flex-wrap gap-1">
                @foreach(array_slice($note->tags, 0, 3) as $tag)
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $tag }}</span>
                @endforeach
                @if(count($note->tags) > 3)
                    <span class="text-xs text-gray-400">+{{ count($note->tags) - 3 }}</span>
                @endif
            </div>
        @endif

        {{-- Linked module --}}
        @if($note->hasLinkedModule())
            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                <x-heroicon-m-link class="w-3 h-3" />
                @if($note->meeting_id)
                    {{ __('my-notes::notes.linked_to') }} {{ $note->meeting?->meeting_number ?? '#' }}
                @elseif($note->project_id)
                    {{ __('my-notes::notes.linked_to') }} {{ $note->project?->name ?? '#' }}
                @endif
            </div>
        @endif

        {{-- Archived label --}}
        @if($note->is_archived)
            <span class="inline-block text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-2 py-0.5 rounded">{{ __('my-notes::notes.archived') }}</span>
        @endif
    </div>

    {{-- Hover actions --}}
    <div class="absolute bottom-0 left-0 right-0 p-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-t border-gray-100 dark:border-gray-700 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-between" onclick="event.stopPropagation()">
        <div class="flex items-center gap-1">
            <x-filament::icon-button wire:click="togglePin('{{ $note->ulid }}')" icon="heroicon-m-map-pin" :color="$note->is_pinned ? 'warning' : 'gray'" size="sm" />
            <x-filament::icon-button wire:click="toggleArchive('{{ $note->ulid }}')" icon="heroicon-m-archive-box" :color="$note->is_archived ? 'primary' : 'gray'" size="sm" />
        </div>
        <x-filament::icon-button wire:click="deleteNote('{{ $note->ulid }}')" icon="heroicon-m-trash" color="danger" size="sm" />
    </div>
</div>
