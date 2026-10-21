@props([
    'variant' => 'full',
])

@php
    $light = $variant === 'compact'
        ? \App\Support\Branding::compactLogoUrl()
        : \App\Support\Branding::fullLogoUrl();
    $dark = $variant === 'compact'
        ? \App\Support\Branding::compactLogoUrl(dark: true)
        : \App\Support\Branding::fullLogoUrl(dark: true);
    $alt = brand_name();
@endphp

@if ($light === $dark)
    <img
        src="{{ $light }}"
        alt="{{ $alt }}"
        {{ $attributes->merge(['decoding' => 'async']) }}
    />
@else
    <img
        src="{{ $light }}"
        alt="{{ $alt }}"
        {{ $attributes->merge(['decoding' => 'async'])->class(['brand-logo--light']) }}
    />
    <img
        src="{{ $dark }}"
        alt="{{ $alt }}"
        {{ $attributes->merge(['decoding' => 'async'])->class(['brand-logo--dark']) }}
    />
@endif
