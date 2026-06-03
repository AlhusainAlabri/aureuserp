@props(['folders', 'currentFolderId' => null, 'depth' => 0])

@foreach ($folders as $folder)
    <button
        wire:click="selectFolder({{ $folder->id }})"
        class="w-full text-left px-3 py-2 rounded text-sm {{ $currentFolderId == $folder->id ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
        style="padding-inline-start: {{ ($depth * 12) + 12 }}px"
    >
        <x-filament::icon :icon="$folder->icon ?? 'heroicon-o-folder'" class="w-4 h-4 inline me-2"/>
        {{ $folder->name }}
    </button>

    @if ($folder->relationLoaded('children') && $folder->children->isNotEmpty())
        @include('document-archive::components.folder-tree', [
            'folders' => $folder->children,
            'currentFolderId' => $currentFolderId,
            'depth' => $depth + 1,
        ])
    @endif
@endforeach
