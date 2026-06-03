@component('mail::message')
# {{ __('inventory-extensions::mail.low_stock_heading', ['count' => $orderPoints->count()]) }}

{{ __('inventory-extensions::mail.low_stock_body') }}

@foreach($orderPoints->take(10) as $point)
- {{ $point->product?->name ?? $point->name }} ({{ __('inventory-extensions::dashboard.low_stock') }})
@endforeach

@component('mail::button', ['url' => $replenishmentUrl])
{{ __('inventory-extensions::mail.low_stock_action') }}
@endcomponent

{{ __('inventory-extensions::mail.regards') }},<br>
{{ brand_name() }}
@endcomponent
