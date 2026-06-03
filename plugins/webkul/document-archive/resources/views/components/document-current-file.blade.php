@props(['record'])

@if ($record)
    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900/40">
        <dl class="grid gap-2 sm:grid-cols-2">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('document-archive::document-archive.fields.reference_number') }}</dt>
                <dd class="font-medium">{{ $record->reference_number }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('document-archive::document-archive.fields.original_filename') }}</dt>
                <dd class="font-medium">{{ $record->original_filename ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('document-archive::document-archive.fields.file_size') }}</dt>
                <dd class="font-medium">{{ $record->getFileSizeForHumans() }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('document-archive::document-archive.fields.version') }}</dt>
                <dd class="font-medium">{{ $record->version }}</dd>
            </div>
        </dl>
    </div>
@endif
