<div class="flex items-center gap-2">
    <x-filament::link
        wire:click="expandAll"
        tag="button"
        size="sm"
        color="primary"
    >
        {{ __('accounting-extensions::reporting.expand-all') }}
    </x-filament::link>

    <span class="flex items-center text-gray-500 dark:text-gray-400">/</span>

    <x-filament::link
        wire:click="collapseAll"
        tag="button"
        size="sm"
        color="primary"
    >
        {{ __('accounting-extensions::reporting.collapse-all') }}
    </x-filament::link>
</div>
