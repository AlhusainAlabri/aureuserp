<x-filament-panels::page>
    @php($overall = $this->overallStatus())

    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('operations.health.overall') }}</p>
                    <p class="text-2xl font-semibold">{{ $overall->getLabel() }}</p>
                </div>
                <x-filament::badge :color="$overall->getColor()" :icon="$overall->getIcon()">
                    {{ __('operations.health.last_checked', ['time' => $this->snapshot['checked_at'] ?? '—']) }}
                </x-filament::badge>
            </div>
        </x-filament::section>

        @if ($this->snapshot)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->snapshot['checks'] ?? [] as $check)
                    @php($status = \App\Enums\Operations\HealthStatus::tryFrom($check['status'] ?? '') ?? \App\Enums\Operations\HealthStatus::Unknown)
                    <x-filament::section>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-medium">{{ $check['label'] ?? $check['key'] }}</h3>
                                <x-filament::badge :color="$status->getColor()">{{ $status->getLabel() }}</x-filament::badge>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $check['summary'] ?? '' }}</p>
                            @if (! empty($check['message']))
                                <p class="text-xs text-warning-600 dark:text-warning-400">{{ $check['message'] }}</p>
                            @endif
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @else
            <x-filament::section>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('operations.health.no_checks_yet') }}</p>
            </x-filament::section>
        @endif

        @if ($this->history() !== [])
            <x-filament::section>
                <x-slot name="heading">{{ __('operations.health.history') }}</x-slot>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->history() as $entry)
                        @php($status = $entry['status'] instanceof \App\Enums\Operations\HealthStatus ? $entry['status'] : \App\Enums\Operations\HealthStatus::tryFrom((string) $entry['status']))
                        <x-filament::badge :color="$status?->getColor() ?? 'gray'">
                            {{ $entry['time'] }} — {{ $status?->getLabel() }}
                        </x-filament::badge>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
