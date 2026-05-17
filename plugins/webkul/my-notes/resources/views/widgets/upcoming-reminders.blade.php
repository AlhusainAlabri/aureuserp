<x-filament-widgets::widget>
    <x-filament::section :heading="$this->getHeading()">
        <div class="space-y-3">
            @forelse($this->reminders as $note)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full" style="background-color: {{ $note->color_hex }}"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $note->auto_title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $note->reminder_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ \Webkul\MyNotes\Filament\Pages\MyNotesPage::getUrl() }}" class="text-sm text-primary-600 hover:text-primary-500">
                        {{ __('my-notes::notes.view_all_notes') }}
                    </a>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                    {{ __('my-notes::notes.empty_subtitle') }}
                </p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
