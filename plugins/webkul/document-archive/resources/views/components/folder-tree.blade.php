@props([
    'folders',
    'currentFolderId' => null,
    'depth' => 0,
    'folderFileCounts' => [],
])

@foreach ($folders as $folder)
    @php
        $hasChildren = $folder->relationLoaded('children') && $folder->children->isNotEmpty();
        $fileCount = $folderFileCounts[$folder->id] ?? 0;
        $isActive = $currentFolderId == $folder->id;
    @endphp

    <div
        @class([
            'relative',
            $depth > 0 ? 'ms-3 border-s border-gray-200 dark:border-gray-700 ps-2' : '',
        ])
    >
        <div
            @class([
                'group/folder flex items-center gap-1 rounded-lg transition',
                $isActive ? 'bg-primary-50 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700/60',
            ])
        >
            @if ($hasChildren)
                <button
                    type="button"
                    @click.stop="expandedIds.includes({{ $folder->id }}) ? expandedIds = expandedIds.filter(id => id !== {{ $folder->id }}) : expandedIds.push({{ $folder->id }})"
                    class="shrink-0 inline-flex h-9 w-7 items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                    aria-label="{{ __('document-archive::document-archive.manager.collapse_folder') }}"
                >
                    <x-filament::icon
                        icon="heroicon-o-chevron-down"
                        class="h-3.5 w-3.5 text-gray-400"
                        x-show="expandedIds.includes({{ $folder->id }})"
                    />
                    <x-filament::icon
                        :icon="app()->getLocale() === 'ar' ? 'heroicon-o-chevron-left' : 'heroicon-o-chevron-right'"
                        class="h-3.5 w-3.5 text-gray-400"
                        x-show="! expandedIds.includes({{ $folder->id }})"
                    />
                </button>
            @else
                <span class="inline-block h-9 w-7 shrink-0" aria-hidden="true"></span>
            @endif

            <button
                type="button"
                wire:click="selectFolder({{ $folder->id }})"
                @class([
                    'flex min-h-9 min-w-0 flex-1 items-center gap-2 rounded-md px-1.5 py-1.5 text-start text-sm',
                    $isActive ? 'font-medium text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-200',
                ])
            >
                <x-filament::icon
                    :icon="$folder->icon ?? 'heroicon-o-folder'"
                    class="h-4 w-4 shrink-0 {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-amber-500' }}"
                />
                <span class="truncate">{{ $folder->name }}</span>
                @if ($fileCount > 0)
                    <span @class([
                        'ms-auto shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium tabular-nums',
                        $isActive
                            ? 'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-200'
                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300',
                    ])>
                        {{ $fileCount }}
                    </span>
                @endif
            </button>

            <div class="relative shrink-0 pe-1" x-data="{ open: false }">
                <button
                    type="button"
                    @click.stop="open = !open"
                    class="inline-flex h-9 w-7 items-center justify-center rounded-md opacity-100 hover:bg-gray-100 dark:hover:bg-gray-700 lg:opacity-0 lg:group-hover/folder:opacity-100"
                    title="{{ __('document-archive::document-archive.manager.actions.more') }}"
                >
                    <x-filament::icon icon="heroicon-o-ellipsis-vertical" class="h-4 w-4 text-gray-500" />
                </button>

                <div
                    x-show="open"
                    x-transition
                    @click.outside="open = false"
                    class="absolute z-30 mt-1 min-w-[11rem] rounded-lg border border-gray-200 bg-white py-1 shadow-lg end-0 dark:border-gray-700 dark:bg-gray-800"
                    style="display: none;"
                >
                    <button
                        type="button"
                        wire:click="mountAction('renameFolder', { folderId: {{ $folder->id }} })"
                        @click="open = false"
                        class="flex min-h-10 w-full items-center gap-2 px-3 py-2 text-sm text-start hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" />
                        {{ __('document-archive::document-archive.manager.rename_folder') }}
                    </button>
                    <button
                        type="button"
                        wire:click="mountAction('moveFolder', { folderId: {{ $folder->id }} })"
                        @click="open = false"
                        class="flex min-h-10 w-full items-center gap-2 px-3 py-2 text-sm text-start hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-4 w-4" />
                        {{ __('document-archive::document-archive.manager.move_folder') }}
                    </button>
                    <button
                        type="button"
                        wire:click="folderPermissions({{ $folder->id }})"
                        @click="open = false"
                        class="flex min-h-10 w-full items-center gap-2 px-3 py-2 text-sm text-start hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4" />
                        {{ __('document-archive::document-archive.manager.folder_permissions') }}
                    </button>
                    <button
                        type="button"
                        wire:click="mountAction('deleteFolder', { folderId: {{ $folder->id }} })"
                        @click="open = false"
                        class="flex min-h-10 w-full items-center gap-2 px-3 py-2 text-sm text-start text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-500/10"
                    >
                        <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                        {{ __('document-archive::document-archive.manager.delete_folder') }}
                    </button>
                </div>
            </div>
        </div>

        @if ($hasChildren)
            <div
                x-show="expandedIds.includes({{ $folder->id }})"
                x-cloak
                class="mt-0.5 space-y-0.5"
            >
                @include('document-archive::components.folder-tree', [
                    'folders' => $folder->children,
                    'currentFolderId' => $currentFolderId,
                    'depth' => $depth + 1,
                    'folderFileCounts' => $folderFileCounts,
                ])
            </div>
        @endif
    </div>
@endforeach
