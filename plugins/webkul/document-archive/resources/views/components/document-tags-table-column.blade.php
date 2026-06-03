@php
    $record = $record ?? (isset($getRecord) ? $getRecord() : null);
@endphp

@if ($record)
    @include('document-archive::components.document-tags', [
        'file' => $record,
        'compact' => true,
        'limit' => 3,
        'showEmpty' => false,
    ])
@endif
