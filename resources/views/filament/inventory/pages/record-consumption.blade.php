<x-filament-panels::page>
    <form wire:submit="recordConsumption">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap gap-3">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    <x-filament::section class="mt-8">
        <x-slot name="heading">
            {{ __('inventory-extensions::consumption.bulk_link') }}
        </x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('inventory-extensions::consumption.bulk_description') }}
        </p>
    </x-filament::section>
</x-filament-panels::page>
