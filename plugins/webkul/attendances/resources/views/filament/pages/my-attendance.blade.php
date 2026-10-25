<x-filament::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col items-center gap-4 py-4 text-center">
                @if ($this->requiresLocation)
                    <div
                        x-data="{
                            busy: false,
                            locate() {
                                this.busy = true

                                const finish = () => { this.busy = false }
                                const send = (latitude, longitude) => $wire.checkInOrOut(latitude, longitude).finally(finish)

                                if (! navigator.geolocation) {
                                    send(null, null)

                                    return
                                }

                                navigator.geolocation.getCurrentPosition(
                                    (position) => send(position.coords.latitude, position.coords.longitude),
                                    () => send(null, null),
                                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
                                )
                            },
                        }"
                    >
                        <x-filament::button
                            x-on:click="locate()"
                            x-bind:disabled="busy"
                            :color="$this->nextDirection === \Webkul\Attendance\Enums\PunchDirection::In ? 'success' : 'danger'"
                            icon="heroicon-o-finger-print"
                            size="xl"
                        >
                            {{ $this->nextDirection === \Webkul\Attendance\Enums\PunchDirection::In
                                ? __('attendances::attendances.my_attendance.check_in')
                                : __('attendances::attendances.my_attendance.check_out') }}
                        </x-filament::button>
                    </div>

                    <p class="text-sm text-gray-500">{{ __('attendances::attendances.my_attendance.location_hint') }}</p>
                @else
                    <x-filament::button
                        wire:click="checkInOrOut"
                        wire:loading.attr="disabled"
                        :color="$this->nextDirection === \Webkul\Attendance\Enums\PunchDirection::In ? 'success' : 'danger'"
                        icon="heroicon-o-finger-print"
                        size="xl"
                    >
                        {{ $this->nextDirection === \Webkul\Attendance\Enums\PunchDirection::In
                            ? __('attendances::attendances.my_attendance.check_in')
                            : __('attendances::attendances.my_attendance.check_out') }}
                    </x-filament::button>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section :heading="__('attendances::attendances.my_attendance.today')">
            @if ($this->todayPunches->isEmpty())
                <p class="text-sm text-gray-500">{{ __('attendances::attendances.my_attendance.no_punches_today') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="text-gray-500">
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.time') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.direction') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.source') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->todayPunches as $punch)
                                <tr wire:key="punch-{{ $punch->id }}">
                                    <td class="px-3 py-2 tabular-nums">{{ $this->localTime($punch->punched_at) }}</td>
                                    <td class="px-3 py-2">
                                        @if ($punch->direction)
                                            <x-filament::badge :color="$punch->direction->getColor()">
                                                {{ $punch->direction->getLabel() }}
                                            </x-filament::badge>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">{{ $punch->source->getLabel() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section :heading="__('attendances::attendances.my_attendance.month')">
            @if ($this->monthDays->isEmpty())
                <p class="text-sm text-gray-500">{{ __('attendances::attendances.my_attendance.no_days') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="text-gray-500">
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.date') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.first_in') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.last_out') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.worked') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.late') }}</th>
                                <th class="px-3 py-2 text-start font-medium">{{ __('attendances::attendances.my_attendance.columns.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->monthDays as $day)
                                <tr wire:key="day-{{ $day->id }}">
                                    <td class="px-3 py-2 tabular-nums">{{ $day->date->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ $this->localTime($day->first_in) }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ $this->localTime($day->last_out) }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ $this->formatMinutes((int) $day->worked_minutes) }}</td>
                                    <td class="px-3 py-2 tabular-nums">{{ (int) $day->late_minutes }}</td>
                                    <td class="px-3 py-2">
                                        <x-filament::badge :color="$day->status->getColor()">
                                            {{ $day->status->getLabel() }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
