<x-mail::message>
# {{ __('inventory-extensions.mail.movement_report_heading') }}

{{ __('inventory-extensions.mail.movement_report_body', [
    'from' => $from->format('d M Y'),
    'to' => $to->format('d M Y'),
]) }}

{{ __('inventory-extensions.mail.movement_report_attachment') }}

{{ __('inventory-extensions.mail.regards') }},<br>
{{ brand_name() }}
</x-mail::message>
