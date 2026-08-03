<x-filament-panels::page>
    {{-- Mobile folder drawer overlay --}}
    @if ($showFolderDrawer)
        <div
            class="fixed inset-0 z-40 bg-gray-950/50 lg:hidden"
            wire:click="toggleFolderDrawer"
        ></div>
    @endif

    <div class="document-manager">
        {{-- Folder sidebar --}}
        <aside @class([
            'document-manager__sidebar',
            'is-open' => $showFolderDrawer,
        ])>
            <div class="flex items-center justify-between gap-2 px-3 py-3 border-b border-gray-200 dark:border-gray-700 lg:border-b-0 lg:pb-2">
                <h3 class="font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ __('document-archive::document-archive.manager.folders') }}
                </h3>
                <button
                    type="button"
                    wire:click="toggleFolderDrawer"
                    class="document-manager__sidebar-close min-h-11 min-w-11 inline-flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                    aria-label="{{ __('document-archive::document-archive.manager.close_folders') }}"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                </button>
            </div>

            <div
                class="space-y-0.5 px-2 pb-3 flex-1 overflow-y-auto"
                x-data="{ expandedIds: @js(array_values($expandedFolderIds)) }"
            >
                <button
                    type="button"
                    wire:click="selectFolder()"
                    @class([
                        'flex w-full items-center gap-2 rounded-lg px-2.5 py-2 min-h-10 text-sm transition',
                        $currentFolderId === null
                            ? 'bg-primary-50 font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'
                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/60',
                    ])
                >
                    <x-filament::icon icon="heroicon-o-folder-open" class="w-4 h-4 shrink-0"/>
                    <span class="truncate">{{ __('document-archive::document-archive.manager.all_files') }}</span>
                </button>

                @include('document-archive::components.folder-tree', [
                    'folders' => $rootFolders,
                    'currentFolderId' => $currentFolderId,
                    'depth' => 0,
                    'folderFileCounts' => $folderFileCounts,
                ])
            </div>
        </aside>

        {{-- Content area --}}
        <section class="document-manager__content">
            {{-- Breadcrumbs --}}
            @if ($breadcrumbs->isNotEmpty())
                <div class="px-4 pt-3 text-sm text-gray-500 flex flex-wrap items-center gap-1">
                    <button type="button" wire:click="selectFolder()" class="hover:text-primary-600 min-h-11 px-1">
                        {{ __('document-archive::document-archive.manager.root') }}
                    </button>
                    @foreach ($breadcrumbs as $crumb)
                        <span>/</span>
                        <button type="button" wire:click="selectFolder({{ $crumb->id }})" class="hover:text-primary-600 min-h-11 px-1">
                            {{ $crumb->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Toolbar --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="toggleFolderDrawer"
                    class="lg:hidden min-h-11 min-w-11 inline-flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                    aria-label="{{ __('document-archive::document-archive.manager.toggle_folders') }}"
                >
                    <x-filament::icon icon="heroicon-o-bars-3" class="w-5 h-5" />
                </button>

                <div class="flex-1 min-w-[12rem]">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            :placeholder="__('document-archive::document-archive.manager.search')"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div class="flex flex-wrap items-center gap-2 max-md:hidden">
                    <x-filament::button
                        size="sm"
                        color="gray"
                        wire:click="toggleFilters"
                        icon="heroicon-m-funnel"
                    >
                        {{ __('document-archive::document-archive.manager.filters') }}
                        @if ($activeFilterCount > 0)
                            <x-filament::badge size="sm" color="primary" class="ms-1">{{ $activeFilterCount }}</x-filament::badge>
                        @endif
                    </x-filament::button>

                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 min-h-11">
                        <input type="checkbox" wire:model.live="includeSubfolders" class="rounded">
                        {{ __('document-archive::document-archive.manager.include_subfolders') }}
                    </label>

                    <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 p-0.5" role="group">
                        @foreach (['grid' => 'heroicon-o-squares-2x2', 'list' => 'heroicon-o-list-bullet'] as $mode => $icon)
                            <button
                                type="button"
                                wire:click="$set('viewMode','{{ $mode }}')"
                                @class([
                                    'p-2 min-h-11 min-w-11 rounded-md inline-flex items-center justify-center',
                                    'bg-primary-100 dark:bg-primary-500/20 text-primary-700' => $viewMode === $mode,
                                    'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600' => $viewMode !== $mode,
                                ])
                                aria-pressed="{{ $viewMode === $mode ? 'true' : 'false' }}"
                                title="{{ __('document-archive::document-archive.manager.view_'.$mode) }}"
                            >
                                <x-filament::icon :icon="$icon" class="w-4 h-4"/>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Mobile overflow menu --}}
                <div class="md:hidden">
                    <x-filament::dropdown>
                        <x-slot name="trigger">
                            <x-filament::icon-button icon="heroicon-m-ellipsis-vertical" :label="__('document-archive::document-archive.manager.actions.more')" />
                        </x-slot>
                        <x-filament::dropdown.list>
                            <x-filament::dropdown.list.item wire:click="toggleFilters" icon="heroicon-m-funnel">
                                {{ __('document-archive::document-archive.manager.filters') }}
                            </x-filament::dropdown.list.item>
                            @foreach (['grid', 'list'] as $mode)
                                <x-filament::dropdown.list.item wire:click="$set('viewMode','{{ $mode }}')">
                                    {{ __('document-archive::document-archive.manager.view_'.$mode) }}
                                </x-filament::dropdown.list.item>
                            @endforeach
                        </x-filament::dropdown.list>
                    </x-filament::dropdown>
                </div>
            </div>

            <p class="px-4 pb-2 text-xs text-gray-500 max-md:hidden">
                {{ __('document-archive::document-archive.manager.view_mode_hint') }}
            </p>

            {{-- Active filter chips --}}
            @if ($activeFilterChips !== [])
                <div class="px-4 pb-3 flex flex-wrap gap-2">
                    @foreach ($activeFilterChips as $chip)
                        <button
                            type="button"
                            wire:click="clearFilter('{{ $chip['key'] }}')"
                            class="inline-flex items-center gap-1 rounded-full bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300 px-3 py-1 text-xs hover:bg-primary-100 dark:hover:bg-primary-500/20 min-h-11 md:min-h-0"
                        >
                            {{ $chip['label'] }}
                            <x-filament::icon icon="heroicon-m-x-mark" class="w-3 h-3" />
                        </button>
                    @endforeach
                    <button type="button" wire:click="resetFilters" class="text-xs text-primary-600 hover:underline min-h-11 md:min-h-0 px-2">
                        {{ __('document-archive::document-archive.manager.reset_filters') }}
                    </button>
                </div>
            @endif

            @if ($showFilters)
                <div class="document-manager-filters border-b border-gray-200 dark:border-gray-700">
                    <div class="document-manager-filters__header">
                        <span class="document-manager-filters__title">
                            {{ __('document-archive::document-archive.manager.filters') }}
                        </span>
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="document-manager-filters__reset"
                        >
                            {{ __('document-archive::document-archive.manager.reset_filters') }}
                        </button>
                    </div>

                    <div class="document-manager-filters__grid">
                        <label class="document-manager-filters__field document-manager-filters__field--toggle">
                            <span class="document-manager-filters__label">{{ __('document-archive::document-archive.manager.filter_expiring') }}</span>
                            <input type="checkbox" wire:model.live="filterExpiringSoon" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600">
                        </label>

                        <div class="document-manager-filters__field">
                            <label class="document-manager-filters__label" for="filter-tag">{{ __('document-archive::document-archive.manager.filter_tag') }}</label>
                            <select id="filter-tag" wire:model.live="filterTag" class="document-manager-filters__input">
                                <option value="">{{ __('document-archive::document-archive.manager.all') }}</option>
                                @foreach ($availableTags as $tag)
                                    <option value="{{ $tag }}">{{ $tag }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="document-manager-filters__field">
                            <label class="document-manager-filters__label" for="filter-extension">{{ __('document-archive::document-archive.fields.extension') }}</label>
                            <select id="filter-extension" wire:model.live="filterExtension" class="document-manager-filters__input">
                                <option value="">{{ __('document-archive::document-archive.manager.all') }}</option>
                                @foreach ($extensionOptions as $extension)
                                    <option value="{{ $extension }}">{{ strtoupper($extension) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="document-manager-filters__field">
                            <label class="document-manager-filters__label" for="filter-privacy">{{ __('document-archive::document-archive.manager.filter_privacy') }}</label>
                            <select id="filter-privacy" wire:model.live="filterPrivate" class="document-manager-filters__input">
                                <option value="">{{ __('document-archive::document-archive.manager.all') }}</option>
                                <option value="1">{{ __('document-archive::document-archive.table.tabs.private') }}</option>
                                <option value="0">{{ __('document-archive::document-archive.manager.public_only') }}</option>
                            </select>
                        </div>

                        @if ($projectOptions !== [])
                            <div class="document-manager-filters__field">
                                <label class="document-manager-filters__label" for="filter-project">{{ __('document-archive::document-archive.fields.project') }}</label>
                                <select id="filter-project" wire:model.live="filterProjectId" class="document-manager-filters__input">
                                    <option value="">{{ __('document-archive::document-archive.manager.all') }}</option>
                                    @foreach ($projectOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($meetingOptions !== [])
                            <div class="document-manager-filters__field">
                                <label class="document-manager-filters__label" for="filter-meeting">{{ __('document-archive::document-archive.fields.meeting') }}</label>
                                <select id="filter-meeting" wire:model.live="filterMeetingId" class="document-manager-filters__input">
                                    <option value="">{{ __('document-archive::document-archive.manager.all') }}</option>
                                    @foreach ($meetingOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($correspondenceOptions !== [])
                            <div class="document-manager-filters__field">
                                <label class="document-manager-filters__label" for="filter-correspondence">{{ __('document-archive::document-archive.fields.correspondence') }}</label>
                                <select id="filter-correspondence" wire:model.live="filterCorrespondenceId" class="document-manager-filters__input">
                                    <option value="">{{ __('document-archive::document-archive.manager.all') }}</option>
                                    @foreach ($correspondenceOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="document-manager-filters__field">
                            <label class="document-manager-filters__label" for="filter-created-from">{{ __('document-archive::document-archive.manager.filter_from') }}</label>
                            <input id="filter-created-from" type="date" wire:model.live="filterCreatedFrom" class="document-manager-filters__input">
                        </div>

                        <div class="document-manager-filters__field">
                            <label class="document-manager-filters__label" for="filter-created-to">{{ __('document-archive::document-archive.manager.filter_to') }}</label>
                            <input id="filter-created-to" type="date" wire:model.live="filterCreatedTo" class="document-manager-filters__input">
                        </div>
                    </div>
                </div>
            @endif

            {{-- Bulk selection bar --}}
            @if ($selectedFiles !== [])
                <div class="px-4 py-3 border-b border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-500/10 flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-primary-800 dark:text-primary-200">
                        {{ __('document-archive::document-archive.manager.selected_count', ['count' => count($selectedFiles)]) }}
                    </span>
                    <x-filament::button size="sm" color="gray" wire:click="mountAction('bulkMove')">
                        {{ __('document-archive::document-archive.manager.bulk.move') }}
                    </x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="mountAction('bulkTags')">
                        {{ __('document-archive::document-archive.manager.bulk.tags') }}
                    </x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="bulkDownloadSelected">
                        {{ __('document-archive::document-archive.manager.bulk.download') }}
                    </x-filament::button>
                    <x-filament::button size="sm" color="danger" wire:click="mountAction('bulkDelete')">
                        {{ __('document-archive::document-archive.manager.bulk.delete') }}
                    </x-filament::button>
                    <button type="button" wire:click="clearSelection" class="text-sm text-primary-600 hover:underline ms-auto min-h-11">
                        {{ __('document-archive::document-archive.manager.clear_selection') }}
                    </button>
                </div>
            @endif

            <div class="flex flex-1 min-h-0">
                <div class="flex-1 overflow-y-auto p-4">
                    @if ($subfolders->isNotEmpty() && $viewMode === 'grid')
                        <div class="mb-4">
                            <h4 class="text-xs uppercase tracking-wider text-gray-500 mb-2">{{ __('document-archive::document-archive.manager.subfolders') }}</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach ($subfolders as $subfolder)
                                    <button
                                        type="button"
                                        wire:click="selectFolder({{ $subfolder->id }})"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-start hover:shadow-sm min-h-11"
                                    >
                                        <x-filament::icon :icon="$subfolder->icon ?? 'heroicon-o-folder'" class="w-6 h-6 mb-2 text-amber-500"/>
                                        <div class="text-sm font-medium truncate">{{ $subfolder->name }}</div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($viewMode === 'grid')
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                            @forelse($files as $file)
                                @php
                                    $fileExists = app(\Webkul\DocumentArchive\Services\DocumentStorageService::class)->fileExists($file);
                                    $expiryColor = null;

                                    if ($file->expiry_date) {
                                        if ($file->expiry_date->lte(now()->startOfDay())) {
                                            $expiryColor = 'text-danger-600';
                                        } elseif ($file->expiry_date->lte(now()->addDays($expiringSoonDays)->startOfDay())) {
                                            $expiryColor = 'text-warning-600';
                                        }
                                    }
                                    $isSelected = in_array($file->id, $selectedFiles, true);
                                @endphp

                                <div
                                    @class([
                                        'document-manager__card group flex flex-col rounded-lg border bg-white p-3 pt-10 transition dark:bg-gray-900/20',
                                        'border-primary-400 shadow-md ring-2 ring-primary-200 dark:ring-primary-500/30' => $isSelected,
                                        'border-gray-200 dark:border-gray-700 hover:shadow-md' => ! $isSelected,
                                    ])
                                >
                                    <label class="document-manager__card-checkbox inline-flex cursor-pointer" wire:click.stop>
                                        <input
                                            type="checkbox"
                                            @checked($isSelected)
                                            wire:click="toggleFileSelection({{ $file->id }})"
                                            aria-label="{{ $file->name }}"
                                            class="size-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600"
                                        />
                                    </label>

                                    @if ($file->extension)
                                        <span class="document-manager__card-badge rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $file->extension }}
                                        </span>
                                    @endif

                                    <div class="mb-3 flex items-center justify-center">
                                        @if($file->isPdf())
                                            <x-filament::icon icon="heroicon-o-document-text" class="h-10 w-10 text-red-500"/>
                                        @elseif($file->isImage())
                                            <x-filament::icon icon="heroicon-o-photo" class="h-10 w-10 text-blue-500"/>
                                        @elseif($file->isOffice())
                                            <x-filament::icon icon="heroicon-o-document" class="h-10 w-10 text-green-500"/>
                                        @else
                                            <x-filament::icon icon="heroicon-o-document" class="h-10 w-10 text-gray-400"/>
                                        @endif

                                        @unless ($fileExists)
                                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="absolute top-12 end-3 h-4 w-4 text-warning-500" />
                                        @endunless
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="openFile({{ $file->id }})"
                                        class="flex-1 text-start focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-md"
                                    >
                                        <p class="text-sm font-medium truncate" title="{{ $file->name }}">{{ $file->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $file->getFileSizeForHumans() }}</p>
                                        <p class="text-xs text-gray-400">{{ $file->reference_number }}</p>

                                        @if ($file->expiry_date)
                                            <p @class(['text-xs mt-1 whitespace-nowrap', $expiryColor ?? 'text-gray-400'])>
                                                {{ __('document-archive::document-archive.fields.expiry_date') }}: {{ $file->expiry_date->format('Y-m-d') }}
                                            </p>
                                        @endif

                                        <div class="mt-2">
                                            @include('document-archive::components.document-tags', [
                                                'file' => $file,
                                                'compact' => true,
                                                'limit' => 2,
                                                'showEmpty' => false,
                                            ])
                                        </div>
                                    </button>

                                    <div class="mt-3 border-t border-gray-100 pt-2 dark:border-gray-700">
                                        @include('document-archive::components.document-file-actions', [
                                            'file' => $file,
                                        ])
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-12 text-gray-400">
                                    <x-filament::icon icon="heroicon-o-folder-open" class="w-12 h-12 mx-auto mb-3"/>
                                    <p class="mb-4">{{ __('document-archive::document-archive.manager.empty') }}</p>
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <x-filament::button wire:click="mountAction('upload')" icon="heroicon-o-arrow-up-tray">
                                            {{ __('document-archive::document-archive.manager.empty_cta_upload') }}
                                        </x-filament::button>
                                        <x-filament::button color="gray" wire:click="mountAction('createFolder')" icon="heroicon-o-folder-plus">
                                            {{ __('document-archive::document-archive.manager.empty_cta_folder') }}
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-start text-gray-500">
                                        <th class="pb-2 pe-2 w-10" aria-hidden="true"></th>
                                        <th class="pb-2 pe-4">{{ __('document-archive::document-archive.fields.name') }}</th>
                                        <th class="pb-2 pe-4 hidden md:table-cell">{{ __('document-archive::document-archive.fields.reference_number') }}</th>
                                        <th class="pb-2 pe-4 hidden lg:table-cell">{{ __('document-archive::document-archive.fields.extension') }}</th>
                                        <th class="pb-2 pe-4 hidden xl:table-cell">{{ __('document-archive::document-archive.fields.tags') }}</th>
                                        <th class="pb-2 pe-4 hidden lg:table-cell">{{ __('document-archive::document-archive.fields.folder') }}</th>
                                        <th class="pb-2 pe-4">{{ __('document-archive::document-archive.fields.file_size') }}</th>
                                        <th class="pb-2 pe-4 hidden md:table-cell">{{ __('document-archive::document-archive.fields.expiry_date') }}</th>
                                        <th class="pb-2 pe-4 hidden lg:table-cell">{{ __('document-archive::document-archive.fields.updated_at') }}</th>
                                        <th class="pb-2">{{ __('document-archive::document-archive.manager.actions.label') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($files as $file)
                                        @php
                                            $fileExists = app(\Webkul\DocumentArchive\Services\DocumentStorageService::class)->fileExists($file);
                                            $expiryColor = null;

                                            if ($file->expiry_date) {
                                                if ($file->expiry_date->lte(now()->startOfDay())) {
                                                    $expiryColor = 'text-danger-600';
                                                } elseif ($file->expiry_date->lte(now()->addDays($expiringSoonDays)->startOfDay())) {
                                                    $expiryColor = 'text-warning-600';
                                                }
                                            }
                                            $isSelected = in_array($file->id, $selectedFiles, true);
                                        @endphp

                                        <tr @class([
                                            'border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
                                            'bg-primary-50/50 dark:bg-primary-500/5' => $isSelected,
                                        ])>
                                            <td class="py-2 pe-2">
                                                <label class="inline-flex items-center justify-center min-h-11 min-w-11 md:min-h-0 md:min-w-0">
                                                    <input
                                                        type="checkbox"
                                                        @checked($isSelected)
                                                        wire:click="toggleFileSelection({{ $file->id }})"
                                                        aria-label="{{ $file->name }}"
                                                        class="rounded size-4 border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600"
                                                    />
                                                </label>
                                            </td>
                                            <td
                                                wire:click="openFile({{ $file->id }})"
                                                class="py-2 pe-4 cursor-pointer font-medium text-primary-600 dark:text-primary-400"
                                            >
                                                <div class="flex items-center gap-2">
                                                    @unless ($fileExists)
                                                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-4 h-4 text-warning-500" />
                                                    @endunless
                                                    <span class="truncate max-w-[12rem]" title="{{ $file->name }}">{{ $file->name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-2 pe-4 text-gray-500 hidden md:table-cell">{{ $file->reference_number }}</td>
                                            <td class="py-2 pe-4 hidden lg:table-cell">
                                                @if ($file->extension)
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ $file->extension }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="py-2 pe-4 hidden xl:table-cell">
                                                @include('document-archive::components.document-tags', [
                                                    'file' => $file,
                                                    'compact' => true,
                                                    'limit' => 2,
                                                    'showEmpty' => false,
                                                ])
                                            </td>
                                            <td class="py-2 pe-4 text-gray-500 hidden lg:table-cell">{{ $file->folder?->name ?? '-' }}</td>
                                            <td class="py-2 pe-4 text-gray-500">{{ $file->getFileSizeForHumans() }}</td>
                                            <td @class(['py-2 pe-4 hidden md:table-cell', $expiryColor ?? 'text-gray-500'])>
                                                {{ $file->expiry_date?->format('Y-m-d') ?? '-' }}
                                            </td>
                                            <td class="py-2 pe-4 text-gray-500 hidden lg:table-cell">{{ $file->updated_at?->diffForHumans() }}</td>
                                            <td class="py-2">
                                                @include('document-archive::components.document-file-actions', [
                                                    'file' => $file,
                                                ])
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="py-12 text-center text-gray-400">
                                                <p class="mb-4">{{ __('document-archive::document-archive.manager.no_results') }}</p>
                                                <x-filament::button wire:click="mountAction('upload')" size="sm">
                                                    {{ __('document-archive::document-archive.manager.empty_cta_upload') }}
                                                </x-filament::button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($files->hasPages())
                        <div class="mt-4">
                            {{ $files->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-2 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 rounded-b-lg">
                @if ($files->total() > 0)
                    {{ __('document-archive::document-archive.manager.items_paginated', [
                        'from' => $files->firstItem(),
                        'to' => $files->lastItem(),
                        'total' => $files->total(),
                    ]) }}
                @else
                    {{ __('document-archive::document-archive.manager.items', ['count' => 0]) }}
                @endif
            </div>
        </section>
    </div>
</x-filament-panels::page>
