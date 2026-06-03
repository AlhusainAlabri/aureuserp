<x-filament::page>
    @if (! $this->employee)
        <x-filament::section>
            <p class="text-sm text-gray-500">{{ __('hr-extensions::profile.no_employee') }}</p>
        </x-filament::section>
    @else
        <x-filament::section :heading="__('hr-extensions::profile.sections.basic')">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.name') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->employee->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.job_title') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->employee->job_title ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.mobile') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->employee->mobile_phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.email') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->employee->work_email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.civil_id') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->employee->civil_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.civil_id_expiry') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->employee->civil_id_expiry?->format('Y-m-d') ?? '—' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section :heading="__('hr-extensions::profile.sections.employment')" class="mt-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.primary_department') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $this->employee->department?->name ?? '—' }}</dd>
                </div>
                @if ($this->hasDepartmentsPivot())
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.all_departments') }}</dt>
                        <dd class="mt-1 flex flex-wrap gap-2">
                            @forelse ($this->employee->departments as $department)
                                <x-filament::badge>{{ $department->complete_name ?? $department->name }}</x-filament::badge>
                            @empty
                                <span class="text-sm text-gray-500">—</span>
                            @endforelse
                        </dd>
                    </div>
                @endif
                @if ($this->hasResponsibilitiesField() && filled($this->employee->primary_job_responsibilities))
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('hr-extensions::profile.fields.responsibilities') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $this->employee->primary_job_responsibilities }}</dd>
                    </div>
                @endif
            </dl>
        </x-filament::section>
    @endif
</x-filament::page>
