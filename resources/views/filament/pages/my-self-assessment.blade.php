<div class="space-y-6">
    <x-filament::section>
        <form wire:submit="submit" class="space-y-4">
            {{ $this->form }}

            <div class="sticky bottom-4 z-10 flex justify-end rounded-xl border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
                <x-filament::button type="submit">
                    {{ __('hr-extensions::self_assessment.actions.submit') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section
        collapsible
        collapsed
        :heading="__('hr-extensions::self_assessment.history_heading')"
    >
        @if ($this->assessments->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('hr-extensions::self_assessment.empty_description') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-3 py-2 text-start">{{ __('hr-extensions::self_assessment.fields.period') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('hr-extensions::self_assessment.fields.status') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('hr-extensions::self_assessment.fields.submitted_at') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('hr-extensions::self_assessment.fields.manager_feedback') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->assessments as $assessment)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2">{{ $assessment->periodLabel() }}</td>
                                <td class="px-3 py-2">{{ $assessment->status->getLabel() }}</td>
                                <td class="px-3 py-2">{{ $assessment->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $assessment->manager_feedback ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</div>
