@php
    $extension  = strtolower(pathinfo($record->file_path, PATHINFO_EXTENSION));
    $isImage    = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp']);
    $isPdf      = $extension === 'pdf';
    $fileExists = \Illuminate\Support\Facades\Storage::disk('local')->exists($record->file_path);
@endphp

{{--
    The modal window is forced to 92vh via extraModalWindowAttributes.
    Filament's fi-modal-content area has no explicit height (it uses overflow-y:auto),
    so flex-1 on children is unreliable. We use calc(92vh - 175px) directly on the
    iframe: 175px ≈ modal header (64px) + footer (64px) + content padding + close button row.
--}}
<div class="-mx-6 -mb-6 overflow-hidden">
    @if (! $fileExists)
        <div class="flex flex-col items-center justify-center gap-3 py-16 text-center text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-12 w-12 text-warning-400" />
            <p class="text-sm font-medium">
                {{ __('employees::filament/resources/employee.relation-manager/documents.preview.file-not-found') }}
            </p>
        </div>

    @elseif ($isImage)
        <div class="flex items-center justify-center overflow-auto bg-gray-50 p-4 dark:bg-gray-800"
             style="height: calc(92vh - 175px);">
            <img
                src="{{ route('employees.documents.preview', $record) }}"
                alt="{{ $record->document_name }}"
                class="max-h-full max-w-full rounded-lg object-contain shadow-lg"
                loading="lazy"
            />
        </div>

    @elseif ($isPdf)
        <iframe
            src="{{ route('employees.documents.preview', $record) }}"
            class="w-full border-0 block"
            style="height: calc(92vh - 175px);"
            title="{{ $record->document_name }}"
        ></iframe>

    @else
        <div class="flex flex-col items-center justify-center gap-4 py-16 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                <x-filament::icon icon="heroicon-o-document" class="h-8 w-8 text-gray-500 dark:text-gray-400" />
            </div>

            <div>
                <p class="text-base font-semibold text-gray-800 dark:text-gray-200">
                    {{ $record->document_name }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('employees::filament/resources/employee.relation-manager/documents.preview.no-preview') }}
                </p>
            </div>

            <a
                href="{{ route('employees.documents.download', $record) }}"
                class="fi-btn fi-btn-size-md inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
            >
                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" />
                {{ __('employees::filament/resources/employee.relation-manager/documents.table.actions.download') }}
            </a>
        </div>
    @endif
</div>
