<x-filament::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- LEFT COLUMN --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Submission Details --}}
            <x-filament::section :heading="__('employees::filament/resources/submission.pages.view-submission.sections.details')">
                <div class="space-y-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-lg font-bold text-gray-900 dark:text-gray-100">{{ $this->getRecord()->ticket_number }}</span>
                        <x-filament::badge :color="$this->getRecord()->type_color" size="sm">
                            {{ __('employees::filament/resources/submission.types.'.$this->getRecord()->type) }}
                        </x-filament::badge>
                        <x-filament::badge :color="$this->getRecord()->priority === 'high' ? 'danger' : ($this->getRecord()->priority === 'medium' ? 'warning' : 'gray')" size="sm">
                            {{ __('employees::filament/resources/submission.priorities.'.$this->getRecord()->priority) }}
                        </x-filament::badge>
                    </div>

                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $this->getRecord()->subject }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{{ $this->getRecord()->body }}</p>

                    @if ($this->getRecord()->attachments)
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-2">{{ __('employees::filament/resources/submission.pages.view-submission.attachments') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->getRecord()->attachments as $attachment)
                                    <a
                                        href="{{ \Illuminate\Support\Facades\Storage::disk('private')->url($attachment) }}"
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
                </div>
            </x-filament::section>

            {{-- Reply Thread --}}
            <x-filament::section :heading="__('employees::filament/resources/submission.pages.view-submission.sections.replies')">
                <div class="space-y-4">
                    @forelse ($this->getRecord()->replies()->with('repliedBy')->get() as $reply)
                        @if ($reply->is_internal)
                            {{-- Internal Note --}}
                            <div class="flex justify-start">
                                <div class="max-w-[85%] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 bg-gray-50 dark:bg-gray-800/50">
                                    <p class="text-xs font-medium text-gray-400 mb-1 flex items-center gap-1">
                                        <x-heroicon-o-lock-closed class="w-3 h-3" />
                                        {{ __('employees::filament/resources/submission.pages.view-submission.internal-note-label') }}
                                        · {{ $reply->repliedBy?->name ?? '—' }}
                                        · {{ $reply->created_at->diffForHumans() }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $reply->body }}</p>
                                </div>
                            </div>
                        @else
                            {{-- External Reply --}}
                            <div class="flex justify-end">
                                <div class="max-w-[85%] bg-primary-100 dark:bg-primary-900/30 rounded-2xl rounded-tr-none px-4 py-3">
                                    <p class="text-xs font-medium text-primary-600 dark:text-primary-400 mb-1">
                                        {{ __('employees::filament/resources/submission.pages.view-submission.hr-team') }}
                                        · {{ $reply->created_at->diffForHumans() }}
                                    </p>
                                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $reply->body }}</p>
                                </div>
                            </div>
                        @endif
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">{{ __('employees::filament/resources/submission.pages.view-submission.no-replies') }}</p>
                    @endforelse
                </div>

                {{-- Reply Form --}}
                <div class="border-t mt-4 pt-4">
                    <div class="space-y-3">
                        <textarea
                            wire:model="replyBody"
                            rows="3"
                            placeholder="{{ __('employees::filament/resources/submission.pages.view-submission.reply-placeholder') }}"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        ></textarea>

                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="replyInternal"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('employees::filament/resources/submission.pages.view-submission.internal-toggle') }}
                                </span>
                            </label>

                            <x-filament::button wire:click="sendReply" color="primary" size="sm">
                                {{ __('employees::filament/resources/submission.pages.view-submission.send-reply') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="space-y-6">
            {{-- Info Card --}}
            <x-filament::section :heading="__('employees::filament/resources/submission.pages.view-submission.sections.info')">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('employees::filament/resources/submission.infolist.sections.details.entries.submitter') }}</span>
                        <span class="font-medium">{{ $this->getRecord()->submitter_name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('employees::filament/resources/submission.infolist.sections.details.entries.department') }}</span>
                        <span class="font-medium">{{ $this->getRecord()->department?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('employees::filament/resources/submission.infolist.sections.details.entries.created-at') }}</span>
                        <span class="font-medium">{{ $this->getRecord()->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ __('employees::filament/resources/submission.infolist.sections.details.entries.status') }}</span>
                        <x-filament::badge :color="$this->getRecord()->status_color" size="sm">
                            {{ __('employees::filament/resources/submission.statuses.'.$this->getRecord()->status) }}
                        </x-filament::badge>
                    </div>
                </div>
            </x-filament::section>

            {{-- Status Timeline --}}
            <x-filament::section :heading="__('employees::filament/resources/submission.pages.view-submission.sections.timeline')">
                <div class="space-y-3">
                    @php
                        $steps = [
                            ['key' => 'open', 'label' => __('employees::filament/resources/submission.statuses.open')],
                            ['key' => 'under_review', 'label' => __('employees::filament/resources/submission.statuses.under_review')],
                            ['key' => 'resolved', 'label' => __('employees::filament/resources/submission.statuses.resolved')],
                            ['key' => 'closed', 'label' => __('employees::filament/resources/submission.statuses.closed')],
                        ];
                        $currentIndex = array_search($this->getRecord()->status, array_column($steps, 'key'));
                    @endphp

                    @foreach ($steps as $index => $step)
                        @php
                            $isActive = $step['key'] === $this->getRecord()->status;
                            $isCompleted = $index < $currentIndex;
                        @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $isActive ? 'bg-primary-500 text-white' : ($isCompleted ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                                @if ($isCompleted)
                                    <x-heroicon-o-check class="w-3 h-3" />
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <span class="text-sm {{ $isActive ? 'font-semibold text-gray-900 dark:text-gray-100' : 'text-gray-500' }}">
                                {{ $step['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            {{-- Quick Actions --}}
            <x-filament::section :heading="__('employees::filament/resources/submission.pages.view-submission.sections.quick-actions')">
                <div class="space-y-2">
                    <x-filament::button
                        wire:click="markUnderReview"
                        color="warning"
                        size="sm"
                        class="w-full"
                        :disabled="$this->getRecord()->status !== 'open'"
                    >
                        {{ __('employees::filament/resources/submission.pages.view-submission.actions.mark-under-review') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="markResolved"
                        color="success"
                        size="sm"
                        class="w-full"
                        :disabled="in_array($this->getRecord()->status, ['resolved', 'closed'])"
                    >
                        {{ __('employees::filament/resources/submission.pages.view-submission.actions.mark-resolved') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="closeTicket"
                        color="gray"
                        size="sm"
                        class="w-full"
                        :disabled="$this->getRecord()->status === 'closed'"
                    >
                        {{ __('employees::filament/resources/submission.pages.view-submission.actions.close-ticket') }}
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament::page>
