@props([
    'status',
    'notes',
    'viewMode' => 'board',
])

@php
    $boardStatus = \Webkul\MyNotes\Enums\NoteBoardStatus::tryFromValue($status);
@endphp

<div class="my-notes-board-column flex min-w-0 flex-col rounded-xl border border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-950/40">
    <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-3 py-2.5 dark:border-gray-700">
        <div class="flex min-w-0 items-center gap-2">
            <x-filament::icon
                :icon="$boardStatus->getIcon()"
                class="h-4 w-4 shrink-0 text-gray-500 dark:text-gray-400"
            />
            <span class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                {{ $boardStatus->getLabel() }}
            </span>
        </div>
        <x-filament::badge :color="$boardStatus->getColor()" size="sm">
            {{ $notes->count() }}
        </x-filament::badge>
    </div>

    <div class="flex flex-1 flex-col gap-3 p-3">
        @forelse($notes as $note)
            @include('my-notes::partials.note-card', ['note' => $note, 'viewMode' => 'grid', 'compact' => true])
        @empty
            <p class="py-8 text-center text-xs text-gray-400 dark:text-gray-500">
                {{ __('my-notes::notes.board.empty_column') }}
            </p>
        @endforelse
    </div>
</div>
