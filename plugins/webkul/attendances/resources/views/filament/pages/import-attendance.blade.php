<x-filament::page>
    <div class="space-y-6">
        <ol class="flex flex-wrap gap-4 text-sm">
            @foreach ([1 => 'upload', 2 => 'mapping', 3 => 'preview'] as $number => $key)
                <li @class([
                    'flex items-center gap-2',
                    'font-semibold text-primary-600 dark:text-primary-400' => $this->step === $number,
                    'text-gray-500' => $this->step !== $number,
                ])>
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border text-xs">{{ $number }}</span>
                    {{ __('attendances::attendances.import.steps.'.$key) }}
                </li>
            @endforeach
        </ol>

        @if ($this->step < 3)
            <form wire:submit="{{ $this->step === 1 ? 'uploadFile' : 'showPreview' }}" class="space-y-6">
                {{ $this->form }}

                <div class="flex flex-wrap gap-3">
                    @if ($this->step === 2)
                        <x-filament::button type="button" color="gray" wire:click="back">
                            {{ __('attendances::attendances.import.actions.back') }}
                        </x-filament::button>
                    @endif

                    <x-filament::button type="submit">
                        {{ $this->step === 1 ? __('attendances::attendances.import.actions.next') : __('attendances::attendances.import.actions.preview') }}
                    </x-filament::button>
                </div>
            </form>
        @else
            <x-filament::section :heading="__('attendances::attendances.import.preview.heading', ['file' => $this->fileName])">
                <dl class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                    <div>
                        <dt class="text-gray-500">{{ __('attendances::attendances.import.preview.total') }}</dt>
                        <dd class="text-lg font-semibold">{{ $this->summary['total'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('attendances::attendances.import.preview.new_punches') }}</dt>
                        <dd class="text-lg font-semibold text-success-600">{{ $this->summary['new_punches'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('attendances::attendances.import.preview.duplicates') }}</dt>
                        <dd class="text-lg font-semibold">{{ $this->summary['duplicates'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('attendances::attendances.import.preview.invalid') }}</dt>
                        <dd class="text-lg font-semibold {{ $this->summary['invalid'] > 0 ? 'text-danger-600' : '' }}">{{ $this->summary['invalid'] }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            @if ($this->summary['unmatched_total'] > 0)
                <x-filament::section :heading="__('attendances::attendances.import.preview.unmatched', ['count' => $this->summary['unmatched_total']])">
                    <p class="mb-2 text-sm text-gray-500">{{ __('attendances::attendances.import.preview.unmatched_help') }}</p>
                    <p class="text-sm" dir="ltr">{{ implode(', ', $this->summary['unmatched']) }}@if ($this->summary['unmatched_total'] > count($this->summary['unmatched'])) ...@endif</p>
                </x-filament::section>
            @endif

            @if ($this->summary['ambiguous_total'] > 0)
                <x-filament::section :heading="__('attendances::attendances.import.preview.ambiguous', ['count' => $this->summary['ambiguous_total']])">
                    <p class="mb-2 text-sm text-gray-500">{{ __('attendances::attendances.import.preview.ambiguous_help') }}</p>
                    <p class="text-sm" dir="ltr">{{ implode(', ', $this->summary['ambiguous']) }}@if ($this->summary['ambiguous_total'] > count($this->summary['ambiguous'])) ...@endif</p>
                </x-filament::section>
            @endif

            <x-filament::section :heading="__('attendances::attendances.import.preview.rows_heading', ['shown' => count($this->summary['rows']), 'total' => $this->summary['new_punches']])">
                @if ($this->summary['rows'] === [])
                    <p class="text-sm text-gray-500">{{ __('attendances::attendances.import.preview.no_rows') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-3 py-2 text-start">{{ __('attendances::attendances.import.preview.columns.employee') }}</th>
                                    <th class="px-3 py-2 text-start">{{ __('attendances::attendances.import.preview.columns.device_id') }}</th>
                                    <th class="px-3 py-2 text-start">{{ __('attendances::attendances.import.preview.columns.time') }}</th>
                                    <th class="px-3 py-2 text-start">{{ __('attendances::attendances.import.preview.columns.direction') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->summary['rows'] as $row)
                                    <tr class="border-b">
                                        <td class="px-3 py-2">{{ $row['employee'] }}</td>
                                        <td class="px-3 py-2" dir="ltr">{{ $row['device_id'] }}</td>
                                        <td class="px-3 py-2" dir="ltr">{{ $row['time'] }}</td>
                                        <td class="px-3 py-2">{{ $row['direction'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            <div class="flex flex-wrap gap-3">
                <x-filament::button type="button" color="gray" wire:click="back">
                    {{ __('attendances::attendances.import.actions.back') }}
                </x-filament::button>

                <x-filament::button type="button" color="gray" wire:click="startOver">
                    {{ __('attendances::attendances.import.actions.start_over') }}
                </x-filament::button>

                <x-filament::button type="button" wire:click="confirm" wire:loading.attr="disabled" wire:target="confirm" :disabled="$this->summary['new_punches'] === 0">
                    {{ __('attendances::attendances.import.actions.confirm') }}
                </x-filament::button>
            </div>
        @endif
    </div>
</x-filament::page>
