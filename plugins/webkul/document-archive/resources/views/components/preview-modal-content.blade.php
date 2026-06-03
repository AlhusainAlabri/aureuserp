@php
    use Webkul\DocumentArchive\Services\DocumentStorageService;

    $storage = app(DocumentStorageService::class);
    $fileExists = $storage->fileExists($file);
    $embedUrl = route('document-archive.preview', ['file' => $file, 'embed' => 1]);
@endphp

{{--
    Modal window uses 92vh via extraModalWindowAttributes on the action.
    calc(92vh - 175px) accounts for modal header, footer, and padding.
--}}
<div class="-mx-6 -mb-6 overflow-hidden">
    @if (! $fileExists)
        <div class="flex flex-col items-center justify-center gap-3 py-16 text-center text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-12 w-12 text-warning-400" />
            <p class="text-sm font-medium">
                {{ __('document-archive::document-archive.preview.file_not_found') }}
            </p>
            <p class="text-xs text-gray-400">{{ $file->reference_number }}</p>
        </div>

    @elseif ($file->isImage())
        <div
            wire:ignore
            x-data="{
                loaded: false,
                src: @js($embedUrl),
            }"
            class="relative flex items-center justify-center overflow-auto bg-gray-50 p-4 dark:bg-gray-800"
            style="height: calc(92vh - 175px);"
        >
            <div
                x-show="! loaded"
                class="absolute inset-0 flex items-center justify-center bg-gray-50 dark:bg-gray-800"
            >
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
                <span class="sr-only">{{ __('document-archive::document-archive.preview.loading') }}</span>
            </div>

            <img
                :src="src"
                @load="loaded = true"
                alt="{{ $file->name }}"
                class="max-h-full max-w-full rounded-lg object-contain shadow-lg"
            />
        </div>

    @elseif ($file->isPdf())
        <div
            wire:ignore
            x-data="{
                loaded: false,
                src: @js($embedUrl),
            }"
            class="relative"
            style="height: calc(92vh - 175px);"
        >
            <div
                x-show="! loaded"
                class="absolute inset-0 z-10 flex items-center justify-center bg-gray-50 dark:bg-gray-800"
            >
                <x-filament::loading-indicator class="h-8 w-8 text-primary-500" />
                <span class="sr-only">{{ __('document-archive::document-archive.preview.loading') }}</span>
            </div>

            <iframe
                :src="src"
                @load="loaded = true"
                class="block h-full w-full border-0"
                title="{{ $file->name }}"
            ></iframe>
        </div>

    @else
        <div class="flex flex-col items-center justify-center gap-4 py-16 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                <x-filament::icon icon="heroicon-o-document" class="h-8 w-8 text-gray-500 dark:text-gray-400" />
            </div>

            <div>
                <p class="text-base font-semibold text-gray-800 dark:text-gray-200">
                    {{ $file->name }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('document-archive::document-archive.preview.no_preview') }}
                </p>
            </div>

            <a
                href="{{ route('document-archive.download', $file) }}"
                class="fi-btn fi-btn-size-md inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
            >
                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" />
                {{ __('document-archive::document-archive.actions.download') }}
            </a>
        </div>
    @endif
</div>
