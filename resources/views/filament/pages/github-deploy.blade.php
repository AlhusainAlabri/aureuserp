<x-filament-panels::page>
    <div @class(['space-y-6']) @if ($this->isDeploying()) wire:poll.3s="refreshDeployState" @endif>
        @if (! ($this->report['enabled'] ?? false))
            <x-filament::section>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.disabled_body') }}</p>
            </x-filament::section>
        @else
            <x-filament::section>
                @if (($this->report['blockers'] ?? []) !== [])
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.unavailable') }}</p>
                @elseif (($this->report['behind'] ?? 0) > 0)
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.pending_count', ['count' => $this->report['behind']]) }}</p>
                @elseif ($this->report['remote_known'] ?? false)
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.up_to_date') }}</p>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.not_checked') }}</p>
                @endif
            </x-filament::section>

            @if (($this->report['commits'] ?? []) !== [])
                <x-filament::section>
                    <x-slot name="heading">{{ __('github-deploy.commits') }}</x-slot>
                    <ul class="space-y-2 text-sm">
                        @foreach ($this->report['commits'] as $commit)
                            <li>{{ $commit['subject'] }}</li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif
        @endif

        @if ($this->isDeploying())
            <x-filament::section>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.installing') }}</p>
            </x-filament::section>
        @elseif (($this->deploy['state'] ?? 'idle') === 'success')
            <x-filament::section>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.completed') }}</p>
            </x-filament::section>
        @elseif (($this->deploy['state'] ?? 'idle') === 'failed')
            <x-filament::section>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('github-deploy.could_not_install') }}</p>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
