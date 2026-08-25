@props([
    'navigationMode' => null,
])

@php
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $collapseIcon = $isRtl
        ? \Filament\Support\Icons\Heroicon::OutlinedChevronRight
        : \Filament\Support\Icons\Heroicon::OutlinedChevronLeft;
    $expandIcon = $isRtl
        ? \Filament\Support\Icons\Heroicon::OutlinedChevronLeft
        : \Filament\Support\Icons\Heroicon::OutlinedChevronRight;
@endphp

<div class="erp-nav-sidebar-toggle">
    <x-filament::icon-button
        color="gray"
        :icon="$collapseIcon"
        :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
        x-cloak
        x-data="{}"
        x-show="$store.sidebar.isOpen"
        x-on:click="$store.sidebar.close()"
        class="erp-nav-sidebar-toggle-btn erp-nav-sidebar-toggle-btn--collapse hidden lg:flex"
    />

    <x-filament::icon-button
        color="gray"
        :icon="$expandIcon"
        :label="__('filament-panels::layout.actions.sidebar.expand.label')"
        x-cloak
        x-data="{}"
        x-show="! $store.sidebar.isOpen"
        x-on:click="$store.sidebar.open()"
        class="erp-nav-sidebar-toggle-btn erp-nav-sidebar-toggle-btn--expand hidden lg:flex"
    />
</div>
