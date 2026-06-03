<x-filament::page>
    @php
        $employee       = $this->getRecord();
        $isInactive     = ! $employee->is_active;
        $alertDocs      = $this->getAlertDocuments();
        $expiredDocs    = $this->getExpiredDocuments();
        $expiringSoon   = $this->getExpiringSoonDocuments();
        $activeWarnings = $this->getActiveWarnings();
        $compliance     = $this->getComplianceAlerts();
        $hasAlerts      = $this->hasAnyAlerts();

        $dangerCompliance  = collect($compliance)->where('color', 'danger');
        $warnCompliance    = collect($compliance)->where('color', 'warning');
        $badCompliance     = $dangerCompliance->merge($warnCompliance);
    @endphp

    <div class="space-y-6">

        {{-- Inactive / Departed Banner --}}
        @if ($isInactive)
            <div class="flex items-center gap-3 rounded-xl border border-danger-200 bg-danger-50 px-5 py-4 dark:border-danger-800 dark:bg-danger-950">
                <x-filament::icon
                    icon="heroicon-o-user-minus"
                    class="h-6 w-6 shrink-0 text-danger-600 dark:text-danger-400"
                />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-danger-800 dark:text-danger-200">
                        {{ __('employees::filament/resources/employee/pages/overview-employee.banner.inactive-title') }}
                    </p>
                    @if ($employee->departure_date)
                        <p class="mt-0.5 text-xs text-danger-600 dark:text-danger-400">
                            {{ __('employees::filament/resources/employee/pages/overview-employee.banner.departed-on', ['date' => $employee->departure_date->format('d M Y')]) }}
                        </p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {{-- Expired Documents --}}
            <div class="flex flex-col gap-1 rounded-xl border p-4
                {{ $expiredDocs->isNotEmpty() ? 'border-danger-200 bg-danger-50 dark:border-danger-800 dark:bg-danger-950' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide
                        {{ $expiredDocs->isNotEmpty() ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ __('employees::filament/resources/employee/pages/overview-employee.summary.expired-docs') }}
                    </span>
                    <x-filament::icon
                        icon="heroicon-o-document-minus"
                        class="h-5 w-5 {{ $expiredDocs->isNotEmpty() ? 'text-danger-500' : 'text-gray-400' }}"
                    />
                </div>
                <span class="text-3xl font-bold {{ $expiredDocs->isNotEmpty() ? 'text-danger-700 dark:text-danger-300' : 'text-gray-700 dark:text-gray-300' }}">
                    {{ $expiredDocs->count() }}
                </span>
            </div>

            {{-- Expiring Soon --}}
            <div class="flex flex-col gap-1 rounded-xl border p-4
                {{ $expiringSoon->isNotEmpty() ? 'border-warning-200 bg-warning-50 dark:border-warning-800 dark:bg-warning-950' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide
                        {{ $expiringSoon->isNotEmpty() ? 'text-warning-600 dark:text-warning-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ __('employees::filament/resources/employee/pages/overview-employee.summary.expiring-soon') }}
                    </span>
                    <x-filament::icon
                        icon="heroicon-o-clock"
                        class="h-5 w-5 {{ $expiringSoon->isNotEmpty() ? 'text-warning-500' : 'text-gray-400' }}"
                    />
                </div>
                <span class="text-3xl font-bold {{ $expiringSoon->isNotEmpty() ? 'text-warning-700 dark:text-warning-300' : 'text-gray-700 dark:text-gray-300' }}">
                    {{ $expiringSoon->count() }}
                </span>
            </div>

            {{-- Active Warnings --}}
            <div class="flex flex-col gap-1 rounded-xl border p-4
                {{ $activeWarnings->isNotEmpty() ? 'border-danger-200 bg-danger-50 dark:border-danger-800 dark:bg-danger-950' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide
                        {{ $activeWarnings->isNotEmpty() ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ __('employees::filament/resources/employee/pages/overview-employee.summary.active-warnings') }}
                    </span>
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="h-5 w-5 {{ $activeWarnings->isNotEmpty() ? 'text-danger-500' : 'text-gray-400' }}"
                    />
                </div>
                <span class="text-3xl font-bold {{ $activeWarnings->isNotEmpty() ? 'text-danger-700 dark:text-danger-300' : 'text-gray-700 dark:text-gray-300' }}">
                    {{ $activeWarnings->count() }}
                </span>
            </div>

            {{-- Compliance Issues --}}
            <div class="flex flex-col gap-1 rounded-xl border p-4
                {{ $badCompliance->isNotEmpty() ? 'border-warning-200 bg-warning-50 dark:border-warning-800 dark:bg-warning-950' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide
                        {{ $badCompliance->isNotEmpty() ? 'text-warning-600 dark:text-warning-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ __('employees::filament/resources/employee/pages/overview-employee.summary.compliance-issues') }}
                    </span>
                    <x-filament::icon
                        icon="heroicon-o-shield-exclamation"
                        class="h-5 w-5 {{ $badCompliance->isNotEmpty() ? 'text-warning-500' : 'text-gray-400' }}"
                    />
                </div>
                <span class="text-3xl font-bold {{ $badCompliance->isNotEmpty() ? 'text-warning-700 dark:text-warning-300' : 'text-gray-700 dark:text-gray-300' }}">
                    {{ $badCompliance->count() }}
                </span>
            </div>
        </div>

        {{-- Quick Info Strip --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ __('employees::filament/resources/employee/pages/overview-employee.info.heading') }}
            </x-slot>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @if ($employee->membership_type && $employee->membership_type !== 'employee')
                    <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-900">
                        <x-filament::badge :color="match ($employee->membership_type) {
                            'collaborator' => 'info',
                            'volunteer' => 'warning',
                            default => 'gray',
                        }">
                            {{ match ($employee->membership_type) {
                                'collaborator' => __('employees::filament/resources/employee.form.sections.fields.collaborator'),
                                'volunteer' => __('employees::filament/resources/employee.form.sections.fields.volunteer'),
                                default => __('employees::filament/resources/employee.form.sections.fields.employee'),
                            } }}
                        </x-filament::badge>
                    </div>
                @endif

                @if ($employee->parent)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon icon="heroicon-o-user" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('employees::filament/resources/employee/pages/overview-employee.info.manager') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $employee->parent->name }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($employee->department)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon icon="heroicon-o-building-office" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('employees::filament/resources/employee/pages/overview-employee.info.department') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $employee->department->name }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($employee->job)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon icon="heroicon-o-briefcase" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('employees::filament/resources/employee/pages/overview-employee.info.job-position') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $employee->job->name }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($employee->work_email)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon icon="heroicon-o-envelope" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('employees::filament/resources/employee/pages/overview-employee.info.work-email') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $employee->work_email }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($employee->work_phone)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('employees::filament/resources/employee/pages/overview-employee.info.work-phone') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $employee->work_phone }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($employee->employmentType)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon icon="heroicon-o-identification" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('employees::filament/resources/employee/pages/overview-employee.info.employment-type') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $employee->employmentType->name }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($employee->civil_id)
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon icon="heroicon-o-identification" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('employees::filament/resources/employee/pages/overview-employee.info.civil-id') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $employee->civil_id }}
                                @if ($employee->civil_id_expiry)
                                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                        ({{ __('employees::filament/resources/employee/pages/overview-employee.info.civil-id-expires') }} {{ $employee->civil_id_expiry->format('d M Y') }})
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Document Alerts --}}
        @if ($alertDocs->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    <span class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-document-minus" class="h-5 w-5 text-danger-500" />
                        {{ __('employees::filament/resources/employee/pages/overview-employee.documents.heading') }}
                    </span>
                </x-slot>

                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('employees::filament/resources/employee/pages/overview-employee.documents.columns.type') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('employees::filament/resources/employee/pages/overview-employee.documents.columns.name') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('employees::filament/resources/employee/pages/overview-employee.documents.columns.expiry-date') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('employees::filament/resources/employee/pages/overview-employee.documents.columns.status') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($alertDocs as $doc)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <x-filament::badge
                                            :color="$this->getDocumentTypeColor($doc->document_type)"
                                            size="sm"
                                        >
                                            {{ $this->getDocumentTypeLabel($doc->document_type) }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $doc->document_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $doc->expiry_date?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($doc->isExpired())
                                            <x-filament::badge color="danger" size="sm">
                                                {{ __('employees::filament/resources/employee/pages/overview-employee.status.expired') }}
                                            </x-filament::badge>
                                        @else
                                            @php
                                                $days = $doc->expiry_date ? (int) now()->diffInDays($doc->expiry_date, false) : null;
                                            @endphp
                                            <x-filament::badge
                                                :color="$days !== null && $days <= 7 ? 'danger' : 'warning'"
                                                size="sm"
                                            >
                                                {{ $days !== null
                                                    ? __('employees::filament/resources/employee/pages/overview-employee.status.expires-in-days', ['days' => $days])
                                                    : '—' }}
                                            </x-filament::badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Compliance Alerts --}}
        @if (! empty($compliance))
            <x-filament::section>
                <x-slot name="heading">
                    <span class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-5 w-5 text-warning-500" />
                        {{ __('employees::filament/resources/employee/pages/overview-employee.compliance.heading') }}
                    </span>
                </x-slot>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($compliance as $item)
                        <div class="flex items-center justify-between rounded-lg border p-4
                            {{ $item['color'] === 'danger'
                                ? 'border-danger-200 bg-danger-50 dark:border-danger-800 dark:bg-danger-950'
                                : ($item['color'] === 'warning'
                                    ? 'border-warning-200 bg-warning-50 dark:border-warning-800 dark:bg-warning-950'
                                    : 'border-success-200 bg-success-50 dark:border-success-800 dark:bg-success-950') }}">
                            <div>
                                <p class="text-xs font-medium
                                    {{ $item['color'] === 'danger'
                                        ? 'text-danger-600 dark:text-danger-400'
                                        : ($item['color'] === 'warning'
                                            ? 'text-warning-600 dark:text-warning-400'
                                            : 'text-success-600 dark:text-success-400') }}">
                                    {{ $item['label'] }}
                                </p>
                                <p class="mt-1 text-sm font-semibold
                                    {{ $item['color'] === 'danger'
                                        ? 'text-danger-800 dark:text-danger-200'
                                        : ($item['color'] === 'warning'
                                            ? 'text-warning-800 dark:text-warning-200'
                                            : 'text-success-800 dark:text-success-200') }}">
                                    {{ $item['date']->format('d M Y') }}
                                </p>
                            </div>
                            <x-filament::badge :color="$item['color']" size="sm">
                                {{ $item['status'] }}
                            </x-filament::badge>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Active Warnings --}}
        @if ($activeWarnings->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    <span class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-danger-500" />
                        {{ __('employees::filament/resources/employee/pages/overview-employee.warnings.heading') }}
                    </span>
                </x-slot>

                <div class="space-y-3">
                    @foreach ($activeWarnings as $warning)
                        <div class="flex items-start gap-4 rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-950">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-900">
                                <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-5 w-5 text-danger-600 dark:text-danger-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-danger-800 dark:text-danger-200">
                                        {{ $warning->subject }}
                                    </p>
                                    @if ($warning->warningType)
                                        <x-filament::badge color="danger" size="sm">
                                            {{ $warning->warningType->name }}
                                        </x-filament::badge>
                                    @endif
                                </div>
                                @if ($warning->description)
                                    <p class="mt-1 text-xs text-danger-600 dark:text-danger-400 line-clamp-2">
                                        {{ $warning->description }}
                                    </p>
                                @endif
                                <p class="mt-1.5 text-xs text-danger-500 dark:text-danger-500">
                                    {{ __('employees::filament/resources/employee/pages/overview-employee.warnings.issued-on', ['date' => $warning->issued_at?->format('d M Y') ?? '—']) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- All Clear Empty State --}}
        @if (! $hasAlerts)
            <div class="flex flex-col items-center justify-center gap-4 py-16 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success-100 dark:bg-success-900">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-10 w-10 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                        {{ __('employees::filament/resources/employee/pages/overview-employee.all-clear.title') }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('employees::filament/resources/employee/pages/overview-employee.all-clear.description') }}
                    </p>
                </div>
            </div>
        @endif

    </div>
</x-filament::page>
