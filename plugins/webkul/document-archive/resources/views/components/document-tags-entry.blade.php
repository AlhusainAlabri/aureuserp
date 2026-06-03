@props(['record'])

@php
    $record = $record ?? (isset($getRecord) ? $getRecord() : null);
@endphp

@if ($record)
    <div class="space-y-3">
        @include('document-archive::components.document-tags', ['file' => $record, 'showEmpty' => true])

        <x-filament::button
            wire:click="mountAction('manageTags')"
            size="sm"
            color="gray"
            icon="heroicon-o-tag"
        >
            {{ $record->getTagsWithColors() === [] ? __('document-archive::document-archive.tags.add') : __('document-archive::document-archive.tags.manage') }}
        </x-filament::button>
    </div>
@endif
