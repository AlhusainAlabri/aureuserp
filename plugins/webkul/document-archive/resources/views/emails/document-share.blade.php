@component('mail::message')
# {{ __('document-archive::document-archive.share.email_heading', ['name' => $file->name]) }}

{{ __('document-archive::document-archive.share.email_body', [
    'reference' => $file->reference_number,
]) }}

@component('mail::button', ['url' => $url])
{{ __('document-archive::document-archive.share.email_button') }}
@endcomponent

@if($link->view_once)
{{ __('document-archive::document-archive.share.email_view_once_note') }}
@endif

@if($link->expires_at)
{{ __('document-archive::document-archive.share.email_expires', ['date' => $link->expires_at->format('d M Y H:i')]) }}
@endif

@endcomponent
