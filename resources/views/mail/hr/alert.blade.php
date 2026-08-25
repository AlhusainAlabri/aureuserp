<x-mail::message>
{{ $body }}

@if ($actionUrl)
<x-mail::button :url="$actionUrl">
{{ $actionLabel }}
</x-mail::button>
@endif

{{ config('app.name') }}
</x-mail::message>
