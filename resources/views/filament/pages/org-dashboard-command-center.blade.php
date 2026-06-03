@php
    $alerts = $this->getOrgDashboardAlerts();
    $totalCount = $this->getOrgDashboardTotalAlertCount();
    $statWidgets = $this->getCommandCenterStatWidgets();
    $leadStatHeading = $this->getOrgDashboardLeadStatHeading();
@endphp

<div class="org-dashboard-command-center fi-wi-widget-width-full col-span-full">
    <div class="org-dashboard-command-center__grid grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
        <x-filament::section
            class="org-dashboard-command-center__alerts h-full"
            :heading="__('dashboard.alerts.panel_title')"
            :description="__('dashboard.alerts.panel_description')"
            icon="heroicon-o-bell-alert"
            collapsible
            persist-collapsed
        >
            <x-slot name="afterHeader">
                @if ($totalCount > 0)
                    <x-filament::badge color="danger">
                        {{ $totalCount }}
                    </x-filament::badge>
                @else
                    <x-filament::badge color="success">
                        {{ __('dashboard.alerts.all_clear_badge') }}
                    </x-filament::badge>
                @endif
            </x-slot>

            @if (count($alerts) === 0)
                <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-success-500/20 bg-success-500/5 px-6 py-10 text-center">
                    <x-filament::icon
                        icon="heroicon-o-check-circle"
                        class="h-10 w-10 text-success-500"
                    />
                    <p class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ __('dashboard.alerts.empty_title') }}
                    </p>
                    <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
                        {{ __('dashboard.alerts.empty_description') }}
                    </p>
                </div>
            @else
                <ul class="max-h-[32rem] divide-y divide-gray-200 overflow-y-auto dark:divide-white/10" role="list">
                    @foreach ($alerts as $alert)
                        @php
                            $severityColor = match ($alert['severity']) {
                                'danger' => 'danger',
                                'warning' => 'warning',
                                default => 'info',
                            };
                            $severityIcon = match ($alert['severity']) {
                                'danger' => 'heroicon-o-exclamation-circle',
                                'warning' => 'heroicon-o-exclamation-triangle',
                                default => 'heroicon-o-information-circle',
                            };
                        @endphp

                        <li>
                            @if (filled($alert['url']))
                                <a
                                    href="{{ $alert['url'] }}"
                                    class="group flex items-center gap-4 px-2 py-3 transition hover:bg-gray-50 dark:hover:bg-white/5"
                                >
                            @else
                                <div class="flex items-center gap-4 px-2 py-3">
                            @endif
                                    <span @class([
                                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                                        'bg-danger-500/10 text-danger-600 dark:text-danger-400' => $alert['severity'] === 'danger',
                                        'bg-warning-500/10 text-warning-600 dark:text-warning-400' => $alert['severity'] === 'warning',
                                        'bg-info-500/10 text-info-600 dark:text-info-400' => $alert['severity'] === 'info',
                                    ])>
                                        <x-filament::icon :icon="$severityIcon" class="h-5 w-5" />
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-filament::badge :color="$severityColor" size="sm">
                                                {{ $alert['module'] }}
                                            </x-filament::badge>
                                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                                {{ $alert['label'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <x-filament::badge :color="$severityColor">
                                        {{ number_format($alert['count']) }}
                                    </x-filament::badge>

                                    @if (filled($alert['url']))
                                        <x-filament::icon
                                            icon="heroicon-o-chevron-right"
                                            class="h-5 w-5 shrink-0 text-gray-400 transition group-hover:text-primary-500 rtl:rotate-180"
                                        />
                                    @endif
                            @if (filled($alert['url']))
                                </a>
                            @else
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>

        <div class="org-dashboard-command-center__stats flex flex-col gap-6">
            @foreach ($statWidgets as $statWidgetClass)
                <div
                    wire:key="command-center-{{ str_replace('\\', '-', $statWidgetClass) }}"
                    @class([
                        'org-dashboard-command-center__stat-block',
                        'org-dashboard-command-center__stat-block--lead' => $loop->first,
                    ])
                >
                    @if ($loop->first && filled($leadStatHeading))
                        <div class="org-dashboard-command-center__stat-toolbar mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                {{ $leadStatHeading }}
                            </h3>

                            <x-filament::dropdown placement="bottom-end" width="md">
                                <x-slot name="trigger">
                                    <x-filament::button
                                        size="sm"
                                        color="gray"
                                        icon="heroicon-o-funnel"
                                        icon-position="before"
                                    >
                                        {{ $this->getOrgDashboardFiltersButtonLabel() }}
                                    </x-filament::button>
                                </x-slot>

                                <div class="org-dashboard-command-center__filters-panel p-4">
                                    <p class="mb-3 text-sm font-medium text-gray-950 dark:text-white">
                                        {{ __('dashboard.filters.title') }}
                                    </p>
                                    <div class="org-dashboard-command-center__filters-fields">
                                        {{ $this->getFiltersForm() }}
                                    </div>
                                </div>
                            </x-filament::dropdown>
                        </div>
                    @endif

                    @livewire($statWidgetClass)
                </div>
            @endforeach
        </div>
    </div>
</div>
