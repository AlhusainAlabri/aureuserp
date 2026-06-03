@props([
    'file',
    'compact' => false,
    'limit' => null,
    'showEmpty' => true,
])

@php
    $tags = collect($file->getTagsWithColors());
    $displayTags = $limit ? $tags->take($limit) : $tags;
    $remaining = $limit ? max($tags->count() - $limit, 0) : 0;
@endphp

@if ($tags->isEmpty() && $showEmpty)
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <x-filament::icon icon="heroicon-o-tag" class="h-4 w-4" />
        <span>{{ __('document-archive::document-archive.tags.empty') }}</span>
    </div>
@elseif ($displayTags->isNotEmpty())
    <div @class([
        'flex flex-wrap gap-1',
        'max-w-xs' => $compact,
    ])>
        @foreach ($displayTags as $tag)
            <span
                @class([
                    'rounded-full text-white',
                    'text-[10px] px-2 py-0.5' => $compact,
                    'text-xs px-2.5 py-1' => ! $compact,
                ])
                style="background-color: {{ $tag['color'] ?? '#64748b' }}"
                title="{{ $tag['name'] }}"
            >
                {{ $tag['name'] }}
            </span>
        @endforeach

        @if ($remaining > 0)
            <span class="text-xs text-gray-500 dark:text-gray-400 self-center">
                +{{ $remaining }}
            </span>
        @endif
    </div>
@endif
