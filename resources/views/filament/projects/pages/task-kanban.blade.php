<x-filament-panels::page>
    @php
        $stages = $this->getStages();
        $tasksByStage = $this->getTasksByStage();
    @endphp

    @if ($stages->isEmpty())
        <x-filament::section>
            <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
                <x-filament::icon icon="heroicon-o-view-columns" class="h-10 w-10 text-gray-400" />
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('tasks.kanban.no_stages') }}</p>
            </div>
        </x-filament::section>
    @else
        <div class="overflow-x-auto pb-4">
            <div class="flex min-w-max gap-4">
                @foreach ($stages as $stage)
                    @php
                        $tasks = $tasksByStage[$stage->id] ?? collect();
                    @endphp

                    <div class="w-80 shrink-0">
                        <div class="mb-3 flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-900">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stage->name }}</h3>
                                <p class="text-xs text-gray-500">{{ $tasks->count() }}</p>
                            </div>
                        </div>

                        <div
                            class="flex min-h-96 flex-col gap-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/50 p-3 dark:border-gray-700 dark:bg-gray-950/40"
                            wire:sort="sortTask"
                            wire:sort:group="task-kanban"
                        >
                            @forelse ($tasks as $task)
                                <div
                                    wire:key="task-card-{{ $task->id }}"
                                    wire:sort.item="{{ $task->id }}"
                                    wire:sort:group="{{ $stage->id }}"
                                    class="cursor-grab rounded-xl border bg-white p-4 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
                                    style="border-inline-start: 4px solid {{ match ($this->priorityColor($task->priority_level ?? null)) {
                                        'danger' => '#EF4444',
                                        'warning' => '#F59E0B',
                                        'info' => '#3B82F6',
                                        default => '#9CA3AF',
                                    } }}"
                                >
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <a
                                            href="{{ $this->taskUrl($task) }}"
                                            class="line-clamp-2 text-sm font-semibold text-gray-900 hover:text-primary-600 dark:text-white"
                                        >
                                            {{ $task->title }}
                                        </a>
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-white"
                                            style="background-color: {{ match ($this->priorityColor($task->priority_level ?? null)) {
                                                'danger' => '#EF4444',
                                                'warning' => '#F59E0B',
                                                'info' => '#3B82F6',
                                                default => '#9CA3AF',
                                            } }}"
                                        >
                                            {{ $this->priorityLabel($task->priority_level ?? null) }}
                                        </span>
                                    </div>

                                    @if ($task->project)
                                        <p class="mb-2 text-xs text-gray-500">{{ $task->project->name }}</p>
                                    @endif

                                    <div class="flex items-center justify-between gap-2 text-xs">
                                        <div class="flex -space-x-2 rtl:space-x-reverse">
                                            @foreach ($task->users->take(3) as $user)
                                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-100 text-[10px] font-semibold text-primary-700 ring-2 ring-white dark:bg-primary-500/20 dark:text-primary-300 dark:ring-gray-900">
                                                    {{ mb_substr($user->name, 0, 1) }}
                                                </span>
                                            @endforeach
                                        </div>

                                        @if ($task->deadline)
                                            <span @class([
                                                'rounded-full px-2 py-0.5 font-medium',
                                                'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => $this->isOverdue($task),
                                                'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' => ! $this->isOverdue($task),
                                            ])>
                                                {{ $task->deadline->format('d M') }}
                                                @if ($this->isOverdue($task))
                                                    · {{ __('tasks.kanban.overdue') }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-gray-200 p-6 text-center text-xs text-gray-400 dark:border-gray-700">
                                    {{ __('tasks.kanban.empty_column') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
