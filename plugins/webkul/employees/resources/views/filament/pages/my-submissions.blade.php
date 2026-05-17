<x-filament::page>
    <div class="space-y-8">
        {{-- Submit Form --}}
        <x-filament::section
            :heading="__('employees::filament/pages/my-submissions.form.section.title')"
            :description="__('employees::filament/pages/my-submissions.form.section.description')"
            collapsible
            collapsed
        >
            <div class="mb-4 text-sm text-gray-500">
                {{ __('employees::filament/pages/my-submissions.form.info-note') }}
            </div>

            <form wire:submit="submit" class="space-y-4">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" color="primary">
                        {{ __('employees::filament/pages/my-submissions.form.submit') }}
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- My Submissions History --}}
        <x-filament::section :heading="__('employees::filament/pages/my-submissions.history.title')">
            @if ($this->mySubmissions->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <x-heroicon-o-inbox class="w-16 h-16 text-gray-300 mb-4" />
                    <p class="text-lg font-medium text-gray-600">
                        {{ __('employees::filament/pages/my-submissions.history.empty.title') }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('employees::filament/pages/my-submissions.history.empty.description') }}
                    </p>
                </div>
            @else
                <div class="grid gap-4">
                    @foreach ($this->mySubmissions as $submission)
                        <div
                            class="relative p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow cursor-pointer"
                            wire:click="openSubmission({{ $submission->id }})"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-2">
                                        <x-filament::badge
                                            :color="$submission->type_color"
                                            size="sm"
                                        >
                                            {{ __('employees::filament/pages/my-submissions.types.'.$submission->type) }}
                                        </x-filament::badge>
                                        <x-filament::badge
                                            :color="$submission->status_color"
                                            size="sm"
                                        >
                                            {{ __('employees::filament/pages/my-submissions.statuses.'.$submission->status) }}
                                        </x-filament::badge>
                                        <span class="text-xs text-gray-400">
                                            {{ $submission->created_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-gray-400 font-mono mb-1">
                                        {{ $submission->ticket_number }}
                                    </p>

                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">
                                        {{ $submission->subject }}
                                    </h3>

                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ Str::limit($submission->body, 120) }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-1 text-sm text-gray-400 shrink-0">
                                    @if ($submission->replies_count > 0)
                                        <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                        <span>{{ $submission->replies_count }}</span>
                                    @endif
                                    <x-heroicon-o-chevron-right class="w-4 h-4 ml-2" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- View Submission Modal --}}
    @if ($this->getViewingSubmission())
        @php $submission = $this->getViewingSubmission(); @endphp
        <x-filament::modal id="view-submission-modal" width="2xl">
            <x-slot name="header">
                <div class="flex items-center gap-2">
                    <x-filament::badge :color="$submission->type_color" size="sm">
                        {{ __('employees::filament/pages/my-submissions.types.'.$submission->type) }}
                    </x-filament::badge>
                    <x-filament::badge :color="$submission->status_color" size="sm">
                        {{ __('employees::filament/pages/my-submissions.statuses.'.$submission->status) }}
                    </x-filament::badge>
                </div>
            </x-slot>

            <div class="space-y-6">
                <div>
                    <p class="text-xs text-gray-400 font-mono">{{ $submission->ticket_number }}</p>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $submission->subject }}</h2>
                    <p class="text-sm text-gray-500 mt-2 whitespace-pre-wrap">{{ $submission->body }}</p>
                </div>

                @if ($submission->attachments)
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">{{ __('employees::filament/pages/my-submissions.modal.attachments') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($submission->attachments as $attachment)
                                <a
                                    href="{{ Storage::disk('private')->url($attachment) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200"
                                >
                                    <x-heroicon-o-paper-clip class="w-4 h-4" />
                                    {{ basename($attachment) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Reply Thread --}}
                @if ($submission->replies->isNotEmpty())
                    <div class="border-t pt-4">
                        <p class="text-sm font-medium text-gray-700 mb-3">{{ __('employees::filament/pages/my-submissions.modal.replies') }}</p>
                        <div class="space-y-3">
                            @foreach ($submission->replies as $reply)
                                <div class="flex justify-start">
                                    <div class="max-w-[80%] bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-none px-4 py-3">
                                        <p class="text-xs font-medium text-gray-500 mb-1">
                                            {{ __('employees::filament/pages/my-submissions.modal.hr-team') }}
                                            · {{ $reply->created_at->diffForHumans() }}
                                        </p>
                                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ $reply->body }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Status Timeline --}}
                <div class="border-t pt-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">{{ __('employees::filament/pages/my-submissions.modal.timeline.title') }}</p>
                    <div class="flex items-center gap-2">
                        @foreach (['open', 'under_review', 'resolved', 'closed'] as $step)
                            @php
                                $isActive = $submission->status === $step;
                                $isPast = in_array($submission->status, array_slice(['resolved', 'closed', 'under_review', 'open'], array_search($step, ['open', 'under_review', 'resolved', 'closed'])));
                                $colors = [
                                    'open' => 'bg-gray-500',
                                    'under_review' => 'bg-amber-500',
                                    'resolved' => 'bg-green-500',
                                    'closed' => 'bg-gray-400',
                                ];
                            @endphp
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded-full {{ $isActive || $isPast ? $colors[$step] : 'bg-gray-200' }}"></div>
                                <span class="text-xs {{ $isActive ? 'font-semibold text-gray-900' : 'text-gray-400' }}">
                                    {{ __('employees::filament/pages/my-submissions.statuses.'.$step) }}
                                </span>
                            </div>
                            @if (! $loop->last)
                                <div class="flex-1 h-px bg-gray-200 mx-1"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <x-slot name="footer">
                <x-filament::button wire:click="closeModal" color="gray">
                    {{ __('employees::filament/pages/my-submissions.modal.close') }}
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endif
</x-filament::page>
