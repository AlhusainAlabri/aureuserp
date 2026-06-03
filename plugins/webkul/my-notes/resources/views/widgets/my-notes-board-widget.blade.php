<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ static::getHeading() }}
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::button
                tag="a"
                :href="$notesUrl"
                size="sm"
                color="gray"
                icon="heroicon-m-arrow-top-right-on-square"
            >
                {{ __('my-notes::notes.actions.view') }}
            </x-filament::button>
        </x-slot>

        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <p class="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <x-heroicon-m-map-pin class="h-3.5 w-3.5 text-warning-500" />
                    {{ __('my-notes::notes.filters.pinned') }}
                </p>
                @if($pinnedNotes->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('my-notes::notes.widget.no_pinned') }}
                    </p>
                @else
                    <div class="space-y-2">
                        @foreach($pinnedNotes as $note)
                            <a
                                href="{{ $notesUrl }}"
                                class="block rounded-lg border border-gray-200 px-3 py-2 transition hover:border-primary-400 dark:border-gray-700 dark:hover:border-primary-500"
                                style="background-color: {{ $note->sticky_background }}"
                            >
                                <span class="line-clamp-1 text-sm font-medium text-gray-950 dark:text-white">
                                    {{ $note->auto_title }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <p class="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <x-heroicon-m-bell class="h-3.5 w-3.5 text-warning-500" />
                    {{ __('my-notes::notes.widget.upcoming_reminders') }}
                </p>
                @if($upcomingReminders->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('my-notes::notes.widget.no_upcoming_reminders') }}
                    </p>
                @else
                    <div class="space-y-2">
                        @foreach($upcomingReminders as $note)
                            <a
                                href="{{ $notesUrl }}"
                                class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 transition hover:border-primary-400 dark:border-gray-700 dark:hover:border-primary-500"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $note->auto_title }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $this->formatReminderAt($note) }}
                                    </span>
                                </span>
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background-color: {{ $note->color_hex }}"
                                ></span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
