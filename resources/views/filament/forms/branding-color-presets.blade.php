@php
    $colorPath = $getContainer()->getStatePath().'.primary_color';
    $defaultKey = array_key_first($presets);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            color: $wire.$entangle('{{ $colorPath }}'),
            isSelected(hex, isDefault) {
                const current = (this.color || '').toLowerCase()

                return isDefault ? current === '' || current === hex : current === hex
            },
        }"
        class="flex flex-wrap gap-3"
        role="radiogroup"
        aria-label="{{ __('branding-settings.form.presets.label') }}"
    >
        @foreach ($presets as $key => $hex)
            @php($isDefault = $key === $defaultKey)

            <button
                type="button"
                role="radio"
                title="{{ __('branding-settings.presets.'.$key) }}"
                x-on:click="color = {{ $isDefault ? 'null' : \Illuminate\Support\Js::from($hex) }}"
                x-bind:aria-checked="isSelected('{{ $hex }}', {{ $isDefault ? 'true' : 'false' }})"
                class="flex flex-col items-center gap-1.5 rounded-lg p-1 text-xs text-gray-600 focus:outline-none dark:text-gray-300"
            >
                <span
                    class="block size-9 rounded-full ring-offset-2 dark:ring-offset-gray-900"
                    style="background-color: {{ $hex }}"
                    x-bind:class="isSelected('{{ $hex }}', {{ $isDefault ? 'true' : 'false' }}) ? 'ring-2 ring-gray-950 dark:ring-white' : 'ring-1 ring-gray-300 dark:ring-gray-600'"
                ></span>
                <span>{{ __('branding-settings.presets.'.$key) }}</span>
            </button>
        @endforeach
    </div>
</x-dynamic-component>
