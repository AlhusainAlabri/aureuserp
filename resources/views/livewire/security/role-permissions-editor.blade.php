<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            @foreach (['resources' => __('role-management.advanced.resources'), 'pages' => __('role-management.advanced.pages'), 'widgets' => __('role-management.advanced.widgets'), 'custom' => __('role-management.advanced.custom')] as $tab => $label)
                <button
                    type="button"
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    @class([
                        'fi-btn fi-size-sm rounded-lg px-3 py-2 text-sm font-medium',
                        'fi-color-custom bg-primary-600 text-white' => $activeTab === $tab,
                        'fi-btn-color-gray bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' => $activeTab !== $tab,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('role-management.advanced.search') }}"
            class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 sm:max-w-xs"
        />
    </div>

    <div class="space-y-4">
        @forelse ($this->getFilteredPermissionGroups() as $module => $options)
            <div wire:key="module-{{ $activeTab }}-{{ $module }}" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $module }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $this->moduleProgress($module, $options) }}</p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="selectModulePermissions('{{ $module }}')"
                            class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-3 py-1.5 text-xs"
                        >
                            {{ __('role-management.advanced.select_module') }}
                        </button>
                        <button
                            type="button"
                            wire:click="deselectModulePermissions('{{ $module }}')"
                            class="fi-btn fi-btn-size-sm fi-color-gray rounded-lg px-3 py-1.5 text-xs"
                        >
                            {{ __('role-management.advanced.deselect_module') }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($options as $permission => $label)
                        <label wire:key="perm-{{ $permission }}" class="flex items-start gap-2 rounded-lg bg-gray-50 p-2 text-sm dark:bg-gray-900/50">
                            <input
                                type="checkbox"
                                wire:model="advancedPermissions"
                                value="{{ $permission }}"
                                class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600"
                            />
                            <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('role-management.summary.no_modules') }}</p>
        @endforelse
    </div>
</div>
