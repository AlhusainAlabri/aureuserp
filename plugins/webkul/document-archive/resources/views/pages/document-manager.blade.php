<x-filament-panels::page>
    <div class="flex gap-4 h-[calc(100vh-12rem)]">
        {{-- Left panel: folder tree --}}
        <div class="w-64 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-y-auto p-3">
            <h3 class="font-semibold text-sm text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wider">
                {{ __('document-archive::document-archive.manager.folders') }}
            </h3>

            <button
                wire:click="selectFolder()"
                class="w-full text-left px-3 py-2 rounded text-sm {{ $currentFolderId === null ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
            >
                <x-filament::icon icon="heroicon-o-folder-open" class="w-4 h-4 inline mr-2"/>
                {{ __('document-archive::document-archive.manager.all_files') }}
            </button>

            @foreach($folders as $folder)
                <button
                    wire:click="selectFolder({{ $folder->id }})"
                    class="w-full text-left px-3 py-2 rounded text-sm {{ $currentFolderId == $folder->id ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                >
                    <x-filament::icon icon="{{ $folder->icon ?? 'heroicon-o-folder' }}" class="w-4 h-4 inline mr-2"/>
                    {{ $folder->name }}
                </button>
            @endforeach
        </div>

        {{-- Content area --}}
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col">
            {{-- Toolbar --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('document-archive::document-archive.manager.search') }}"
                    class="flex-1 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-1.5 text-sm"
                />
                <button
                    wire:click="$set('viewMode','grid')"
                    class="p-1.5 rounded {{ $viewMode == 'grid' ? 'bg-primary-100 dark:bg-primary-500/20' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    title="Grid"
                >
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="w-4 h-4"/>
                </button>
                <button
                    wire:click="$set('viewMode','list')"
                    class="p-1.5 rounded {{ $viewMode == 'list' ? 'bg-primary-100 dark:bg-primary-500/20' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    title="List"
                >
                    <x-filament::icon icon="heroicon-o-list-bullet" class="w-4 h-4"/>
                </button>
                <button
                    wire:click="uploadFile"
                    class="px-3 py-1.5 rounded bg-primary-600 text-white text-sm hover:bg-primary-700"
                >
                    <x-filament::icon icon="heroicon-o-arrow-up-tray" class="w-4 h-4 inline mr-1"/>
                    {{ __('document-archive::document-archive.actions.download') ?? 'Upload' }}
                </button>
                <button
                    wire:click="createFolder"
                    class="px-3 py-1.5 rounded bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                    <x-filament::icon icon="heroicon-o-folder-plus" class="w-4 h-4 inline mr-1"/>
                    {{ __('document-archive::document-archive.navigation.folders.label') }}
                </button>
            </div>

            {{-- Files --}}
            <div class="flex-1 overflow-y-auto p-4">
                @if($viewMode === 'grid')
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @forelse($files as $file)
                            <div
                                wire:click="openFile({{ $file->id }})"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md cursor-pointer transition"
                            >
                                <div class="text-3xl mb-2 text-center">
                                    @if($file->isPdf())
                                        <x-filament::icon icon="heroicon-o-document-text" class="w-8 h-8 mx-auto text-red-500"/>
                                    @elseif($file->isImage())
                                        <x-filament::icon icon="heroicon-o-photo" class="w-8 h-8 mx-auto text-blue-500"/>
                                    @elseif($file->isOffice())
                                        <x-filament::icon icon="heroicon-o-document" class="w-8 h-8 mx-auto text-green-500"/>
                                    @else
                                        <x-filament::icon icon="heroicon-o-document" class="w-8 h-8 mx-auto text-gray-400"/>
                                    @endif
                                </div>
                                <p class="text-sm font-medium truncate">{{ $file->name }}</p>
                                <p class="text-xs text-gray-500">{{ $file->getFileSizeForHumans() }}</p>
                                <p class="text-xs text-gray-400">{{ $file->reference_number }}</p>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 text-gray-400">
                                <x-filament::icon icon="heroicon-o-folder-open" class="w-12 h-12 mx-auto mb-3"/>
                                <p>{{ __('document-archive::document-archive.manager.empty') }}</p>
                            </div>
                        @endforelse
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500">
                                <th class="pb-2">{{ __('document-archive::document-archive.fields.name') }}</th>
                                <th class="pb-2">{{ __('document-archive::document-archive.fields.reference_number') }}</th>
                                <th class="pb-2">{{ __('document-archive::document-archive.fields.file_size') }}</th>
                                <th class="pb-2">{{ __('document-archive::document-archive.fields.updated_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($files as $file)
                                <tr
                                    wire:click="openFile({{ $file->id }})"
                                    class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    <td class="py-2">{{ $file->name }}</td>
                                    <td class="py-2 text-gray-500">{{ $file->reference_number }}</td>
                                    <td class="py-2 text-gray-500">{{ $file->getFileSizeForHumans() }}</td>
                                    <td class="py-2 text-gray-500">{{ $file->updated_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-gray-400">
                                        {{ __('document-archive::document-archive.manager.no_results') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Status bar --}}
            <div class="p-2 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 bg-gray-50 dark:bg-gray-900 rounded-b-lg">
                {{ __('document-archive::document-archive.manager.items', ['count' => $files->count()]) }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
