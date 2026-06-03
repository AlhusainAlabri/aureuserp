<x-filament-widgets::widget>
    @if ($substituteCoverages->isNotEmpty())
        <div class="mb-4 space-y-2">
            @foreach ($substituteCoverages as $leave)
                <x-filament::callout color="info" icon="heroicon-o-user-group">
                    {{ __('hr-extensions::leave.covering_for', [
                        'employee' => $leave->employee?->name ?? '—',
                        'start' => $leave->date_from?->format('d M Y') ?? '—',
                        'end' => $leave->date_to?->format('d M Y') ?? '—',
                    ]) }}
                </x-filament::callout>
            @endforeach
        </div>
    @endif

    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.widgets.my_tasks') }}
        </x-slot>

        @if ($tasks->isEmpty())
            <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-8 w-8 text-success-500" />
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.all_caught_up') }}</p>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($tasks as $task)
                    <div @class([
                        'flex items-start justify-between gap-3 py-3',
                        'border-s-4 border-danger-500 ps-3' => $task['is_overdue'],
                    ])>
                        <div class="min-w-0 flex-1">
                            @if ($task['url'])
                                <a href="{{ $task['url'] }}" class="line-clamp-1 text-sm font-medium text-gray-900 hover:text-primary-600 dark:text-white">
                                    {{ $task['title'] }}
                                </a>
                            @else
                                <p class="line-clamp-1 text-sm font-medium text-gray-900 dark:text-white">{{ $task['title'] }}</p>
                            @endif
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span>{{ $task['status'] }}</span>
                                <span>·</span>
                                <span>{{ $task['priority'] }}</span>
                                @if ($task['due_date'])
                                    <span>·</span>
                                    <span @class(['text-danger-600 dark:text-danger-400' => $task['is_overdue']])>
                                        {{ $task['due_date']->format('d M Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <x-filament::badge :color="$task['source'] === 'project' ? 'primary' : 'warning'">
                            {{ $task['source'] === 'project' ? __('tasks.calendar.project_task') : __('tasks.calendar.meeting_task') }}
                        </x-filament::badge>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
