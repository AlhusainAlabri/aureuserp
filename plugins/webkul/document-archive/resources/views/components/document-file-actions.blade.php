@props([
    'file',
    'viewMode' => 'grid',
])

@php
    $isExplorer = $viewMode === 'explorer';
@endphp

<div class="flex items-center gap-1" x-data="{ open: false }" @click.stop>
    <button
        type="button"
        wire:click="openFile({{ $file->id }})"
        class="p-1.5 rounded hover:bg-primary-50 dark:hover:bg-primary-500/10 text-primary-600 dark:text-primary-400"
        title="{{ __('document-archive::document-archive.actions.preview') }}"
    >
        <x-filament::icon icon="heroicon-o-eye" class="w-4 h-4" />
    </button>

    <button
        type="button"
        wire:click="downloadFile({{ $file->id }})"
        class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
        title="{{ __('document-archive::document-archive.actions.download') }}"
    >
        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-4 h-4" />
    </button>

    <div class="relative">
        <button
            type="button"
            @click="open = !open"
            class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
            title="{{ __('document-archive::document-archive.manager.actions.more') }}"
        >
            <x-filament::icon icon="heroicon-o-ellipsis-vertical" class="w-4 h-4" />
        </button>

        <div
            x-show="open"
            x-transition
            @click.outside="open = false"
            class="absolute z-20 mt-1 min-w-[10rem] rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg end-0 py-1"
            style="display: none;"
        >
            <button
                type="button"
                wire:click="viewFile({{ $file->id }})"
                @click="open = false"
                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-start hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
                {{ __('document-archive::document-archive.actions.view') }}
            </button>

            <button
                type="button"
                wire:click="shareFile({{ $file->id }})"
                @click="open = false"
                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-start hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                <x-filament::icon icon="heroicon-o-share" class="w-4 h-4" />
                {{ __('document-archive::document-archive.actions.share') }}
            </button>

            <button
                type="button"
                wire:click="manageTagsFile({{ $file->id }})"
                @click="open = false"
                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-start hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                <x-filament::icon icon="heroicon-o-tag" class="w-4 h-4" />
                {{ __('document-archive::document-archive.tags.manage') }}
            </button>
        </div>
    </div>
</div>
