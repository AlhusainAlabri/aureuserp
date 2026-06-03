<x-filament-panels::page>
    <div @class([
        'flex gap-4 h-[calc(100vh-12rem)]',
        'flex-col lg:flex-row',
    ])>
        {{-- Left panel: nested folder tree --}}
        <div class="w-full lg:w-64 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-y-auto p-3 shrink-0">
            <h3 class="font-semibold text-sm text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wider">
                {{ __('document-archive::document-archive.manager.folders') }}
            </h3>

            <button
                wire:click="selectFolder()"
                class="w-full text-start px-3 py-2 rounded text-sm {{ $currentFolderId === null ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
            >
                <x-filament::icon icon="heroicon-o-folder-open" class="w-4 h-4 inline me-2"/>
                {{ __('document-archive::document-archive.manager.all_files') }}
            </button>

            @include('document-archive::components.folder-tree', [
                'folders' => $rootFolders,
                'currentFolderId' => $currentFolderId,
                'depth' => 0,
            ])
        </div>

        {{-- Content area --}}
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col min-w-0">
            {{-- Breadcrumbs --}}
            @if ($breadcrumbs->isNotEmpty())
                <div class="px-4 pt-3 text-sm text-gray-500 flex flex-wrap gap-1">
                    <button wire:click="selectFolder()" class="hover:text-primary-600">
                        {{ __('document-archive::document-archive.manager.root') }}
                    </button>
                    @foreach ($breadcrumbs as $crumb)
                        <span>/</span>
                        <button wire:click="selectFolder({{ $crumb->id }})" class="hover:text-primary-600">
                            {{ $crumb->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Toolbar --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('document-archive::document-archive.manager.search') }}"
                    class="flex-1 min-w-[12rem] rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm"
                />

                <button
                    wire:click="toggleFilters"
                    class="px-3 py-1.5 rounded text-sm border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    {{ __('document-archive::document-archive.manager.filters') }}
                </button>

                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" wire:model.live="includeSubfolders" class="rounded">
                    {{ __('document-archive::document-archive.manager.include_subfolders') }}
                </label>

                <button
                    wire:click="$set('viewMode','grid')"
                    class="p-1.5 rounded {{ $viewMode == 'grid' ? 'bg-primary-100 dark:bg-primary-500/20' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    title="{{ __('document-archive::document-archive.manager.view_grid') }}"
                >
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="w-4 h-4"/>
                </button>
                <button
                    wire:click="$set('viewMode','list')"
                    class="p-1.5 rounded {{ $viewMode == 'list' ? 'bg-primary-100 dark:bg-primary-500/20' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    title="{{ __('document-archive::document-archive.manager.view_list') }}"
                >
                    <x-filament::icon icon="heroicon-o-list-bullet" class="w-4 h-4"/>
                </button>
                <button
                    wire:click="$set('viewMode','explorer')"
                    class="p-1.5 rounded {{ $viewMode == 'explorer' ? 'bg-primary-100 dark:bg-primary-500/20' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    title="{{ __('document-archive::document-archive.manager.view_explorer') }}"
                >
                    <x-filament::icon icon="heroicon-o-computer-desktop" class="w-4 h-4"/>
                </button>

                <button
                    wire:click="createFolder"
                    class="px-3 py-1.5 rounded bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                    <x-filament::icon icon="heroicon-o-folder-plus" class="w-4 h-4 inline me-1"/>
                    {{ __('document-archive::document-archive.navigation.folders.label') }}
                </button>
            </div>

            @if ($showFilters)
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <select wire:model.live="filterTag" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm">
                        <option value="">{{ __('document-archive::document-archive.manager.filter_tag') }}</option>
                        @foreach ($availableTags as $tag)
                            <option value="{{ $tag }}">{{ $tag }}</option>
                        @endforeach
                    </select>

                    @if ($projectOptions !== [])
                        <select wire:model.live="filterProjectId" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm">
                            <option value="">{{ __('document-archive::document-archive.fields.project') }}</option>
                            @foreach ($projectOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($meetingOptions !== [])
                        <select wire:model.live="filterMeetingId" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm">
                            <option value="">{{ __('document-archive::document-archive.fields.meeting') }}</option>
                            @foreach ($meetingOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif

                    <select wire:model.live="filterExtension" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm">
                        <option value="">{{ __('document-archive::document-archive.fields.extension') }}</option>
                        @foreach ($extensionOptions as $extension)
                            <option value="{{ $extension }}">{{ strtoupper($extension) }}</option>
                        @endforeach
                    </select>

                    <input type="date" wire:model.live="filterCreatedFrom" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm">
                    <input type="date" wire:model.live="filterCreatedTo" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm">

                    <select wire:model.live="filterPrivate" class="rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm">
                        <option value="">{{ __('document-archive::document-archive.manager.filter_privacy') }}</option>
                        <option value="1">{{ __('document-archive::document-archive.table.tabs.private') }}</option>
                        <option value="0">{{ __('document-archive::document-archive.manager.public_only') }}</option>
                    </select>

                    <button wire:click="resetFilters" class="text-sm text-primary-600 hover:underline text-start">
                        {{ __('document-archive::document-archive.manager.reset_filters') }}
                    </button>
                </div>
            @endif

            <div class="flex flex-1 min-h-0">
                <div class="flex-1 overflow-y-auto p-4">
                    @if ($subfolders->isNotEmpty() && in_array($viewMode, ['grid', 'explorer']))
                        <div class="mb-4">
                            <h4 class="text-xs uppercase tracking-wider text-gray-500 mb-2">{{ __('document-archive::document-archive.manager.subfolders') }}</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach ($subfolders as $subfolder)
                                    <button
                                        wire:click="selectFolder({{ $subfolder->id }})"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-start hover:shadow-sm"
                                    >
                                        <x-filament::icon :icon="$subfolder->icon ?? 'heroicon-o-folder'" class="w-6 h-6 mb-2 text-amber-500"/>
                                        <div class="text-sm font-medium truncate">{{ $subfolder->name }}</div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($viewMode === 'grid' || $viewMode === 'explorer')
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
                                @endphp

                                <div
                                    wire:click="{{ $viewMode === 'explorer' ? 'selectFile('.$file->id.')' : 'openFile('.$file->id.')' }}"
                                    @class([
                                        'group relative border rounded-lg p-3 cursor-pointer transition',
                                        'border-primary-400 shadow-md' => $viewMode === 'explorer' && $selectedFileId === $file->id,
                                        'border-gray-200 dark:border-gray-700 hover:shadow-md' => ! ($viewMode === 'explorer' && $selectedFileId === $file->id),
                                    ])
                                >
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="text-3xl">
                                            @if($file->isPdf())
                                                <x-filament::icon icon="heroicon-o-document-text" class="w-8 h-8 text-red-500"/>
                                            @elseif($file->isImage())
                                                <x-filament::icon icon="heroicon-o-photo" class="w-8 h-8 text-blue-500"/>
                                            @elseif($file->isOffice())
                                                <x-filament::icon icon="heroicon-o-document" class="w-8 h-8 text-green-500"/>
                                            @else
                                                <x-filament::icon icon="heroicon-o-document" class="w-8 h-8 text-gray-400"/>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-1">
                                            @unless ($fileExists)
                                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-4 h-4 text-warning-500" />
                                            @endunless
                                            @if ($file->extension)
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ $file->extension }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="text-sm font-medium truncate" title="{{ $file->name }}">{{ $file->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $file->getFileSizeForHumans() }}</p>
                                    <p class="text-xs text-gray-400">{{ $file->reference_number }}</p>

                                    @if ($file->expiry_date)
                                        <p @class(['text-xs mt-1', $expiryColor ?? 'text-gray-400'])>
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

                                    <div class="mt-3 border-t border-gray-100 pt-2 dark:border-gray-700 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                        @include('document-archive::components.document-file-actions', [
                                            'file' => $file,
                                            'viewMode' => $viewMode,
                                        ])
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-12 text-gray-400">
                                    <x-filament::icon icon="heroicon-o-folder-open" class="w-12 h-12 mx-auto mb-3"/>
                                    <p>{{ __('document-archive::document-archive.manager.empty') }}</p>
                                </div>
                            @endforelse
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-start text-gray-500">
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
                                        @endphp

                                        <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
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
                                                    'viewMode' => 'list',
                                                ])
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="py-12 text-center text-gray-400">
                                                {{ __('document-archive::document-archive.manager.no_results') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($viewMode === 'explorer')
                    <div class="w-80 border-s border-gray-200 dark:border-gray-700 p-4 overflow-y-auto shrink-0">
                        <h4 class="font-semibold text-sm mb-3">{{ __('document-archive::document-archive.manager.details') }}</h4>

                        @if ($selectedFile)
                            @php
                                $selectedFileExists = app(\Webkul\DocumentArchive\Services\DocumentStorageService::class)->fileExists($selectedFile);
                            @endphp

                            @unless ($selectedFileExists)
                                <div class="mb-3 rounded-lg border border-danger-300 bg-danger-50 px-3 py-2 text-xs text-danger-700 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-300">
                                    {{ __('document-archive::document-archive.missing_file.title') }}
                                </div>
                            @endunless

                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-gray-500">{{ __('document-archive::document-archive.fields.name') }}</dt>
                                    <dd class="font-medium">{{ $selectedFile->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('document-archive::document-archive.fields.reference_number') }}</dt>
                                    <dd>{{ $selectedFile->reference_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('document-archive::document-archive.fields.folder') }}</dt>
                                    <dd>{{ $selectedFile->folder?->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('document-archive::document-archive.fields.file_size') }}</dt>
                                    <dd>{{ $selectedFile->getFileSizeForHumans() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ __('document-archive::document-archive.fields.tags') }}</dt>
                                    <dd class="mt-1">
                                        @include('document-archive::components.document-tags', [
                                            'file' => $selectedFile,
                                            'compact' => true,
                                            'showEmpty' => true,
                                        ])
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex flex-col gap-2">
                                <button
                                    wire:click="openFile({{ $selectedFile->id }})"
                                    @disabled(! $selectedFileExists)
                                    @class([
                                        'px-3 py-2 rounded text-white text-sm',
                                        'bg-primary-600 hover:bg-primary-500' => $selectedFileExists,
                                        'bg-gray-300 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-400' => ! $selectedFileExists,
                                    ])
                                >
                                    {{ __('document-archive::document-archive.actions.preview') }}
                                </button>
                                <button
                                    wire:click="downloadFile({{ $selectedFile->id }})"
                                    @disabled(! $selectedFileExists)
                                    @class([
                                        'px-3 py-2 rounded text-sm',
                                        'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600' => $selectedFileExists,
                                        'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-500' => ! $selectedFileExists,
                                    ])
                                >
                                    {{ __('document-archive::document-archive.actions.download') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="viewFile({{ $selectedFile->id }})"
                                    class="px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-sm text-center hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    {{ __('document-archive::document-archive.actions.view') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="shareFile({{ $selectedFile->id }})"
                                    class="px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-sm text-center hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    {{ __('document-archive::document-archive.actions.share') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="manageTagsFile({{ $selectedFile->id }})"
                                    class="px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-sm text-center hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    {{ __('document-archive::document-archive.tags.manage') }}
                                </button>
                            </div>
                        @else
                            <p class="text-sm text-gray-400">{{ __('document-archive::document-archive.manager.select_file') }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="p-2 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 rounded-b-lg">
                {{ __('document-archive::document-archive.manager.items', ['count' => $files->count()]) }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
