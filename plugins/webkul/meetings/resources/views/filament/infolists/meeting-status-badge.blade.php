@php
    use Webkul\Meetings\Filament\Resources\MeetingResource;

    /** @var \Webkul\Meetings\Models\Meeting $record */
    $record = $getRecord();
    $canChangeStatus = auth()->user()?->can('updateStatus', $record) ?? false;
    $statusLabel = MeetingResource::statusOptions()[$record->status] ?? $record->status;
    $statusColor = $record->status_color;
@endphp

<div class="fi-in-entry">
    <dt class="fi-in-entry-label">
        {{ __('meetings::meetings.fields.status') }}
    </dt>
    <dd class="fi-in-entry-content">
        @if ($canChangeStatus)
            <button
                type="button"
                wire:click="mountAction('changeStatus')"
                class="fi-badge fi-size-md fi-color-{{ $statusColor }} cursor-pointer transition hover:opacity-80"
                title="{{ __('meetings::meetings.actions.change_status_hint') }}"
            >
                {{ $statusLabel }}
            </button>
        @else
            <x-filament::badge :color="$statusColor">
                {{ $statusLabel }}
            </x-filament::badge>
        @endif
    </dd>
</div>
