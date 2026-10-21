<?php

namespace Webkul\DocumentArchive\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\WithPagination;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\DocumentArchive\Filament\Actions\ManageDocumentTagsAction;
use Webkul\DocumentArchive\Filament\Actions\PreviewDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\ShareDocumentAction;
use Webkul\DocumentArchive\Filament\Concerns\ManagesDocumentRecords;
use Webkul\DocumentArchive\Filament\Forms\DocumentFileQuickCreateForm;
use Webkul\DocumentArchive\Filament\Forms\DocumentTagForm;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentStorageService;
use Webkul\DocumentArchive\Services\DocumentTagService;
use Webkul\DocumentArchive\Support\DocumentDashboardCache;
use Webkul\DocumentArchive\Support\FilamentUrl;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;

class DocumentManager extends Page
{
    use HasPageShield;
    use ManagesDocumentRecords;
    use WithPagination;

    protected static ?string $slug = 'document-manager';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?int $navigationSort = 60;

    protected string $view = 'document-archive::pages.document-manager';

    public ?int $currentFolderId = null;

    public string $search = '';

    public string $viewMode = 'grid';

    public bool $showFilters = false;

    public bool $includeSubfolders = false;

    public bool $filterExpiringSoon = false;

    public ?string $filterTag = null;

    public ?int $filterProjectId = null;

    public ?int $filterMeetingId = null;

    public ?int $filterCorrespondenceId = null;

    public ?string $filterExtension = null;

    public ?string $filterCreatedFrom = null;

    public ?string $filterCreatedTo = null;

    public ?string $filterPrivate = null;

    /** @var array<int, int> */
    public array $selectedFiles = [];

    public bool $showFolderDrawer = false;

    public int $perPage = 24;

    /** @var array<int, int> */
    public array $expandedFolderIds = [];

    protected static function getPagePermission(): ?string
    {
        return 'view_any_document_archive_doc::file';
    }

    public static function getNavigationLabel(): string
    {
        return __('document-archive::document-archive.navigation.documents.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.document-archive');
    }

    public function getTitle(): string|Htmlable
    {
        return __('document-archive::document-archive.manager.title');
    }

    public function mount(): void
    {
        if (filled(request()->query('folder'))) {
            $this->currentFolderId = (int) request()->query('folder');
        }

        if (request()->query('filter') === 'expiring') {
            $this->filterExpiringSoon = true;
            $this->showFilters = true;
        }

        if (request()->query('filter') === 'private') {
            $this->filterPrivate = '1';
            $this->showFilters = true;
        }

        if ($this->currentFolderId) {
            $this->expandFolderPath($this->currentFolderId);
        }

        if ($this->viewMode === 'explorer') {
            $this->viewMode = 'grid';
        }
    }

    public function updatedViewMode(string $value): void
    {
        if ($value === 'explorer') {
            $this->viewMode = 'grid';
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('document-archive::document-archive.actions.upload'))
                ->icon('heroicon-o-arrow-up-tray')
                ->schema(DocumentFileQuickCreateForm::uploadTabs($this->currentFolderId))
                ->modalSubmitActionLabel(__('document-archive::document-archive.actions.upload'))
                ->extraModalFooterActions([
                    Action::make('openFullForm')
                        ->label(__('document-archive::document-archive.manager.open_full_form'))
                        ->url(fn (): string => FilamentUrl::appendLocaleToUrl(
                            DocFileResource::getUrl('create', array_filter([
                                'folder_id' => $this->currentFolderId,
                            ]))
                        ))
                        ->link()
                        ->openUrlInNewTab(),
                ])
                ->action(function (array $data): void {
                    $this->createUploadedFile($data);
                }),
            Action::make('createFolder')
                ->label(__('document-archive::document-archive.manager.create_folder'))
                ->icon('heroicon-o-folder-plus')
                ->schema($this->folderFormSchema())
                ->action(function (array $data): void {
                    $this->createFolderRecord($data);
                }),
        ];
    }

    protected function renameFolderAction(): Action
    {
        return Action::make('renameFolder')
            ->label(__('document-archive::document-archive.manager.rename_folder'))
            ->icon('heroicon-o-pencil-square')
            ->schema([
                TextInput::make('name')
                    ->label(__('document-archive::document-archive.fields.name'))
                    ->required()
                    ->maxLength(255),
            ])
            ->fillForm(fn (array $arguments): array => [
                'name' => DocFolder::query()->find($arguments['folderId'] ?? null)?->name ?? '',
            ])
            ->action(function (array $data, array $arguments): void {
                $folder = DocFolder::query()->find($arguments['folderId'] ?? null);

                if (! $folder || ! $this->canManageFolder($folder)) {
                    return;
                }

                $folder->update(['name' => $data['name']]);

                Notification::make()
                    ->title(__('document-archive::document-archive.manager.folder_renamed'))
                    ->success()
                    ->send();
            });
    }

    protected function moveFolderAction(): Action
    {
        return Action::make('moveFolder')
            ->label(__('document-archive::document-archive.manager.move_folder'))
            ->icon('heroicon-o-arrows-right-left')
            ->schema([
                Select::make('parent_id')
                    ->label(__('document-archive::document-archive.fields.parent'))
                    ->options(fn (): array => $this->folderParentOptions(
                        $this->getMountedAction()?->getArguments()['folderId'] ?? null,
                    ))
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ])
            ->fillForm(fn (array $arguments): array => [
                'parent_id' => DocFolder::query()->find($arguments['folderId'] ?? null)?->parent_id,
            ])
            ->action(function (array $data, array $arguments): void {
                $folder = DocFolder::query()->find($arguments['folderId'] ?? null);

                if (! $folder || ! $this->canManageFolder($folder)) {
                    return;
                }

                $folder->update(['parent_id' => $data['parent_id'] ?? null]);

                Notification::make()
                    ->title(__('document-archive::document-archive.manager.folder_moved'))
                    ->success()
                    ->send();
            });
    }

    protected function deleteFolderAction(): Action
    {
        return Action::make('deleteFolder')
            ->label(__('document-archive::document-archive.manager.delete_folder'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('document-archive::document-archive.manager.delete_folder_confirm'))
            ->action(function (array $arguments): void {
                $folder = DocFolder::query()->find($arguments['folderId'] ?? null);

                if (! $folder || ! $this->canManageFolder($folder)) {
                    return;
                }

                if ($folder->files()->exists()) {
                    Notification::make()
                        ->title(__('document-archive::document-archive.manager.delete_folder_has_files'))
                        ->danger()
                        ->send();

                    return;
                }

                if ($folder->children()->exists()) {
                    Notification::make()
                        ->title(__('document-archive::document-archive.manager.delete_folder_has_children'))
                        ->danger()
                        ->send();

                    return;
                }

                if ($this->currentFolderId === $folder->id) {
                    $this->currentFolderId = $folder->parent_id;
                }

                $folder->delete();

                Notification::make()
                    ->title(__('document-archive::document-archive.manager.folder_deleted'))
                    ->success()
                    ->send();
            });
    }

    protected function bulkMoveAction(): Action
    {
        return Action::make('bulkMove')
            ->label(__('document-archive::document-archive.manager.bulk.move'))
            ->icon('heroicon-o-folder-arrow-down')
            ->schema([
                Select::make('folder_id')
                    ->label(__('document-archive::document-archive.fields.folder'))
                    ->options(fn (): array => DocFolder::query()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->bulkMoveFiles((int) $data['folder_id']);
            });
    }

    protected function bulkTagsAction(): Action
    {
        return Action::make('bulkTags')
            ->label(__('document-archive::document-archive.manager.bulk.tags'))
            ->icon('heroicon-o-tag')
            ->schema(DocumentTagForm::schema(includeAdvanced: false))
            ->action(function (array $data): void {
                $this->bulkApplyTags($data);
            });
    }

    protected function bulkDeleteAction(): Action
    {
        return Action::make('bulkDelete')
            ->label(__('document-archive::document-archive.manager.bulk.delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(fn (): string => __('document-archive::document-archive.manager.bulk.delete_confirm', [
                'count' => count($this->selectedFiles),
            ]))
            ->action(function (): void {
                $this->bulkDeleteFiles();
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createUploadedFile(array $data): void
    {
        $storage = app(DocumentStorageService::class);
        $tagService = app(DocumentTagService::class);
        $accessService = app(DocumentAccessService::class);

        $file = DocFile::query()->create([
            'name'               => $storage->resolveDisplayName($data['upload'] ?? null, $data['name'] ?? null),
            'folder_id'          => $data['folder_id'],
            'description'        => $data['description'] ?? null,
            'tags'               => $accessService->normalizeTags($tagService->resolveTagsFromInput($data)),
            'is_private'         => (bool) ($data['is_private'] ?? false),
            'expiry_date'        => $data['expiry_date'] ?? null,
            'project_id'         => $data['project_id'] ?? null,
            'meeting_id'         => $data['meeting_id'] ?? null,
            'correspondence_id'  => $data['correspondence_id'] ?? null,
            'company_id'         => $this->defaultCompanyId(),
            'creator_id'         => Auth::id(),
            ...$storage->pendingFileAttributes(),
        ]);

        $storage->attachToFile($file, $data['upload']);
        $this->handleDocumentPassword($file, $data);

        Notification::make()
            ->title(__('document-archive::document-archive.notifications.uploaded.title'))
            ->body(__('document-archive::document-archive.notifications.uploaded.body', [
                'reference' => $file->reference_number,
            ]))
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createFolderRecord(array $data): void
    {
        DocFolder::query()->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'parent_id'   => $data['parent_id'] ?? $this->currentFolderId,
            'color'       => $data['color'] ?? null,
            'icon'        => $data['icon'] ?? null,
            'company_id'  => $this->defaultCompanyId(),
            'creator_id'  => Auth::id(),
        ]);

        Notification::make()
            ->title(__('document-archive::document-archive.manager.folder_created'))
            ->success()
            ->send();
    }

    /**
     * @return array<int, Component>
     */
    protected function folderFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('document-archive::document-archive.fields.name'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('document-archive::document-archive.fields.description'))
                ->columnSpanFull(),
            Select::make('parent_id')
                ->label(__('document-archive::document-archive.fields.parent'))
                ->options(fn (): array => DocFolder::query()->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->default($this->currentFolderId),
            ColorPicker::make('color')
                ->label(__('document-archive::document-archive.fields.color')),
            TextInput::make('icon')
                ->label(__('document-archive::document-archive.fields.icon'))
                ->placeholder('heroicon-o-folder')
                ->maxLength(64),
        ];
    }

    public function selectFolder(?int $folderId = null): void
    {
        $this->currentFolderId = $folderId;
        $this->selectedFiles = [];
        $this->showFolderDrawer = false;

        if ($folderId !== null) {
            $this->expandFolderPath($folderId);
            $this->ensureFolderExpanded($folderId);
        }

        $this->resetPage();
    }

    public function toggleFolderDrawer(): void
    {
        $this->showFolderDrawer = ! $this->showFolderDrawer;
    }

    public function toggleFolderExpanded(int $folderId): void
    {
        if (in_array($folderId, $this->expandedFolderIds, true)) {
            $this->expandedFolderIds = array_values(array_diff($this->expandedFolderIds, [$folderId]));
        } else {
            $this->expandedFolderIds[] = $folderId;
        }
    }

    protected function expandFolderPath(int $folderId): void
    {
        $folder = DocFolder::query()->find($folderId);

        if (! $folder) {
            return;
        }

        foreach ($folder->getBreadcrumbs()->slice(0, -1) as $ancestor) {
            $this->ensureFolderExpanded($ancestor->id);
        }
    }

    protected function ensureFolderExpanded(int $folderId): void
    {
        if (! in_array($folderId, $this->expandedFolderIds, true)) {
            $this->expandedFolderIds[] = $folderId;
        }
    }

    public function openFile(int $fileId): void
    {
        $file = DocFile::query()->find($fileId);

        if (! $file) {
            return;
        }

        if (PreviewDocumentAction::needsPasswordPage($file)) {
            $this->redirect(route('document-archive.preview', ['file' => $file->id]));

            return;
        }

        if (! PreviewDocumentAction::canPreview($file)) {
            return;
        }

        $this->mountAction('previewDocument', ['fileId' => $fileId]);
    }

    protected function previewDocumentAction(): Action
    {
        return PreviewDocumentAction::makeForFileId();
    }

    protected function shareDocumentAction(): Action
    {
        return ShareDocumentAction::makeForFileId();
    }

    protected function manageTagsAction(): Action
    {
        return ManageDocumentTagsAction::makeForFileId();
    }

    public function viewFile(int $fileId): void
    {
        $this->redirect(DocFileResource::getUrl('view', FilamentUrl::withLocale(['record' => $fileId])));
    }

    public function shareFile(int $fileId): void
    {
        $this->mountAction('shareDocument', ['fileId' => $fileId]);
    }

    public function manageTagsFile(int $fileId): void
    {
        $this->mountAction('manageTags', ['fileId' => $fileId]);
    }

    public function downloadFile(int $fileId): void
    {
        $this->redirect(route('document-archive.download', ['file' => $fileId]));
    }

    public function folderPermissions(int $folderId): void
    {
        $this->redirect(DocFolderResource::getUrl('view', FilamentUrl::withLocale(['record' => $folderId])));
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->filterExpiringSoon = false;
        $this->filterTag = null;
        $this->filterProjectId = null;
        $this->filterMeetingId = null;
        $this->filterCorrespondenceId = null;
        $this->filterExtension = null;
        $this->filterCreatedFrom = null;
        $this->filterCreatedTo = null;
        $this->filterPrivate = null;
        $this->resetPage();
    }

    public function clearFilter(string $filter): void
    {
        match ($filter) {
            'expiring'       => $this->filterExpiringSoon = false,
            'tag'            => $this->filterTag = null,
            'project'        => $this->filterProjectId = null,
            'meeting'        => $this->filterMeetingId = null,
            'correspondence' => $this->filterCorrespondenceId = null,
            'extension'      => $this->filterExtension = null,
            'created_from'   => $this->filterCreatedFrom = null,
            'created_to'     => $this->filterCreatedTo = null,
            'private'        => $this->filterPrivate = null,
            default          => null,
        };

        $this->resetPage();
    }

    public function toggleFileSelection(int $fileId): void
    {
        if (in_array($fileId, $this->selectedFiles, true)) {
            $this->selectedFiles = array_values(array_diff($this->selectedFiles, [$fileId]));
        } else {
            $this->selectedFiles[] = $fileId;
        }
    }

    public function selectAllOnPage(): void
    {
        $this->selectedFiles = $this->files
            ->getCollection()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->files
            ->getCollection()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($pageIds === []) {
            return;
        }

        $allSelected = count(array_intersect($pageIds, $this->selectedFiles)) === count($pageIds);

        if ($allSelected) {
            $this->selectedFiles = array_values(array_diff($this->selectedFiles, $pageIds));
        } else {
            $this->selectedFiles = array_values(array_unique([...$this->selectedFiles, ...$pageIds]));
        }
    }

    public function getIsAllOnPageSelectedProperty(): bool
    {
        $pageIds = $this->files
            ->getCollection()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($pageIds === []) {
            return false;
        }

        return count(array_intersect($pageIds, $this->selectedFiles)) === count($pageIds);
    }

    public function clearSelection(): void
    {
        $this->selectedFiles = [];
    }

    public function bulkDownloadSelected(): void
    {
        if ($this->selectedFiles === []) {
            return;
        }

        $files = DocFile::query()->whereIn('id', $this->selectedFiles)->get();
        $user = Auth::user();

        foreach ($files as $file) {
            if ($user?->can('download', $file)) {
                $this->redirect(route('document-archive.download', ['file' => $file->id]));

                return;
            }
        }

        Notification::make()
            ->title(__('document-archive::document-archive.manager.bulk.download_denied'))
            ->danger()
            ->send();
    }

    protected function bulkMoveFiles(int $folderId): void
    {
        $user = Auth::user();
        $moved = 0;

        foreach (DocFile::query()->whereIn('id', $this->selectedFiles)->get() as $file) {
            if ($user?->can('update', $file)) {
                $file->update(['folder_id' => $folderId]);
                $moved++;
            }
        }

        $this->selectedFiles = [];

        Notification::make()
            ->title(__('document-archive::document-archive.manager.bulk.moved'))
            ->body(__('document-archive::document-archive.manager.bulk.moved_body', ['count' => $moved]))
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function bulkApplyTags(array $data): void
    {
        $tagService = app(DocumentTagService::class);
        $accessService = app(DocumentAccessService::class);
        $tags = $accessService->normalizeTags($tagService->resolveTagsFromInput($data));
        $user = Auth::user();
        $updated = 0;

        foreach (DocFile::query()->whereIn('id', $this->selectedFiles)->get() as $file) {
            if ($user?->can('update', $file)) {
                $file->update(['tags' => $tags]);
                $updated++;
            }
        }

        $this->selectedFiles = [];

        Notification::make()
            ->title(__('document-archive::document-archive.manager.bulk.tags_applied'))
            ->body(__('document-archive::document-archive.manager.bulk.tags_applied_body', ['count' => $updated]))
            ->success()
            ->send();
    }

    protected function bulkDeleteFiles(): void
    {
        $user = Auth::user();
        $deleted = 0;

        foreach (DocFile::query()->whereIn('id', $this->selectedFiles)->get() as $file) {
            if ($user?->can('delete', $file)) {
                $file->delete();
                $deleted++;
            }
        }

        $this->selectedFiles = [];

        Notification::make()
            ->title(__('document-archive::document-archive.manager.bulk.deleted'))
            ->body(__('document-archive::document-archive.manager.bulk.deleted_body', ['count' => $deleted]))
            ->success()
            ->send();
    }

    public function getActiveFilterCountProperty(): int
    {
        $count = 0;

        if ($this->filterExpiringSoon) {
            $count++;
        }

        if (filled($this->filterTag)) {
            $count++;
        }

        if ($this->filterProjectId) {
            $count++;
        }

        if ($this->filterMeetingId) {
            $count++;
        }

        if ($this->filterCorrespondenceId) {
            $count++;
        }

        if (filled($this->filterExtension)) {
            $count++;
        }

        if ($this->filterCreatedFrom) {
            $count++;
        }

        if ($this->filterCreatedTo) {
            $count++;
        }

        if ($this->filterPrivate !== null && $this->filterPrivate !== '') {
            $count++;
        }

        return $count;
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function getActiveFilterChipsProperty(): array
    {
        $chips = [];

        if ($this->filterExpiringSoon) {
            $chips[] = [
                'key'   => 'expiring',
                'label' => __('document-archive::document-archive.table.tabs.expiring_soon'),
            ];
        }

        if (filled($this->filterTag)) {
            $chips[] = [
                'key'   => 'tag',
                'label' => __('document-archive::document-archive.manager.filter_tag').': '.$this->filterTag,
            ];
        }

        if ($this->filterProjectId) {
            $chips[] = [
                'key'   => 'project',
                'label' => __('document-archive::document-archive.fields.project').': '.($this->projectOptions()[$this->filterProjectId] ?? $this->filterProjectId),
            ];
        }

        if ($this->filterMeetingId) {
            $chips[] = [
                'key'   => 'meeting',
                'label' => __('document-archive::document-archive.fields.meeting').': '.($this->meetingOptions()[$this->filterMeetingId] ?? $this->filterMeetingId),
            ];
        }

        if ($this->filterCorrespondenceId) {
            $chips[] = [
                'key'   => 'correspondence',
                'label' => __('document-archive::document-archive.fields.correspondence').': '.($this->correspondenceOptions()[$this->filterCorrespondenceId] ?? $this->filterCorrespondenceId),
            ];
        }

        if (filled($this->filterExtension)) {
            $chips[] = [
                'key'   => 'extension',
                'label' => __('document-archive::document-archive.fields.extension').': '.strtoupper($this->filterExtension),
            ];
        }

        if ($this->filterCreatedFrom) {
            $chips[] = [
                'key'   => 'created_from',
                'label' => __('document-archive::document-archive.manager.filter_from').': '.$this->filterCreatedFrom,
            ];
        }

        if ($this->filterCreatedTo) {
            $chips[] = [
                'key'   => 'created_to',
                'label' => __('document-archive::document-archive.manager.filter_to').': '.$this->filterCreatedTo,
            ];
        }

        if ($this->filterPrivate !== null && $this->filterPrivate !== '') {
            $chips[] = [
                'key'   => 'private',
                'label' => $this->filterPrivate === '1'
                    ? __('document-archive::document-archive.table.tabs.private')
                    : __('document-archive::document-archive.manager.public_only'),
            ];
        }

        return $chips;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedIncludeSubfolders(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTag(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProjectId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMeetingId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCorrespondenceId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterExtension(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCreatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCreatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPrivate(): void
    {
        $this->resetPage();
    }

    public function updatedFilterExpiringSoon(): void
    {
        $this->resetPage();
    }

    public function getRootFoldersProperty(): EloquentCollection
    {
        return once(fn (): EloquentCollection => DocFolder::buildTree());
    }

    /**
     * @return array<int, int>
     */
    public function getAllFolderIdsProperty(): array
    {
        return once(fn (): array => DocFolder::query()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all());
    }

    public function getCurrentFolderProperty(): ?DocFolder
    {
        if (! $this->currentFolderId) {
            return null;
        }

        return DocFolder::query()->find($this->currentFolderId);
    }

    public function getBreadcrumbsProperty(): Collection
    {
        return $this->currentFolder?->getBreadcrumbs() ?? collect();
    }

    public function getSubfoldersProperty(): EloquentCollection|Collection
    {
        if (! $this->currentFolderId) {
            return collect();
        }

        return DocFolder::query()
            ->where('parent_id', $this->currentFolderId)
            ->ordered()
            ->get();
    }

    public function getFilesProperty(): LengthAwarePaginator
    {
        return $this->buildFilesQuery()->paginate($this->perPage);
    }

    protected function buildFilesQuery(): Builder
    {
        $query = DocFile::query()->with('folder');

        app(DocumentAccessService::class)->applyAccessibleFilesScope($query);

        if ($this->currentFolderId) {
            if ($this->includeSubfolders) {
                $folder = DocFolder::query()->find($this->currentFolderId);

                if ($folder) {
                    $folderIds = $folder->getAllDescendants()->pluck('id')->push($folder->id)->all();
                    $query->whereIn('folder_id', $folderIds);
                }
            } else {
                $query->where('folder_id', $this->currentFolderId);
            }
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('reference_number', 'like', $term)
                    ->orWhere('original_filename', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (filled($this->filterTag)) {
            $query->withTag($this->filterTag);
        }

        if ($this->filterProjectId) {
            $query->where('project_id', $this->filterProjectId);
        }

        if ($this->filterMeetingId) {
            $query->where('meeting_id', $this->filterMeetingId);
        }

        if ($this->filterCorrespondenceId) {
            $query->where('correspondence_id', $this->filterCorrespondenceId);
        }

        if (filled($this->filterExtension)) {
            $query->where('extension', $this->filterExtension);
        }

        if ($this->filterCreatedFrom) {
            $query->whereDate('created_at', '>=', $this->filterCreatedFrom);
        }

        if ($this->filterCreatedTo) {
            $query->whereDate('created_at', '<=', $this->filterCreatedTo);
        }

        if ($this->filterPrivate !== null && $this->filterPrivate !== '') {
            $query->where('is_private', (bool) $this->filterPrivate);
        }

        if ($this->filterExpiringSoon) {
            $query->expiringSoon((int) config('document-archive.expiring_soon_days', 7));
        }

        return $query->orderByDesc('created_at');
    }

    public function getAvailableTagsProperty(): array
    {
        return array_values(app(DocumentTagService::class)->suggestions());
    }

    /**
     * @return array<int, int>
     */
    public function getFolderFileCountsProperty(): array
    {
        return DocumentDashboardCache::rememberGlobal('folder_file_counts', fn (): array => DocFile::query()
            ->selectRaw('folder_id, count(*) as aggregate')
            ->groupBy('folder_id')
            ->pluck('aggregate', 'folder_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all());
    }

    protected function getViewData(): array
    {
        return [
            'rootFolders'           => $this->rootFolders,
            'breadcrumbs'           => $this->breadcrumbs,
            'subfolders'            => $this->subfolders,
            'files'                 => $this->files,
            'availableTags'         => $this->availableTags,
            'activeFilterCount'     => $this->activeFilterCount,
            'activeFilterChips'     => $this->activeFilterChips,
            'folderFileCounts'      => $this->folderFileCounts,
            'expandedFolderIds'     => $this->allFolderIds,
            'projectOptions'        => $this->projectOptions(),
            'meetingOptions'        => $this->meetingOptions(),
            'correspondenceOptions' => $this->correspondenceOptions(),
            'extensionOptions'      => DocFile::query()->distinct()->pluck('extension', 'extension')->filter()->all(),
            'expiringSoonDays'      => (int) config('document-archive.expiring_soon_days', 7),
        ];
    }

    /**
     * @return array<int|string, string>
     */
    protected function projectOptions(): array
    {
        if (! class_exists(Project::class) || ! DbSchema::hasTable('projects_projects')) {
            return [];
        }

        return Project::query()->pluck('name', 'id')->all();
    }

    /**
     * @return array<int|string, string>
     */
    protected function meetingOptions(): array
    {
        if (! class_exists(Meeting::class) || ! DbSchema::hasTable('meetings')) {
            return [];
        }

        return Meeting::query()->pluck('title', 'id')->all();
    }

    /**
     * @return array<int|string, string>
     */
    protected function correspondenceOptions(): array
    {
        if (! class_exists(Correspondence::class)) {
            return [];
        }

        if (! DbSchema::hasTable('correspondences')) {
            return [];
        }

        return Correspondence::query()
            ->pluck('subject', 'id')
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    protected function folderParentOptions(?int $excludeFolderId): array
    {
        if (! $excludeFolderId) {
            return DocFolder::query()->pluck('name', 'id')->all();
        }

        $folder = DocFolder::query()->find($excludeFolderId);

        if (! $folder) {
            return DocFolder::query()->pluck('name', 'id')->all();
        }

        $excludeIds = $folder->getAllDescendants()->pluck('id')->push($folder->id)->all();

        return DocFolder::query()
            ->whereNotIn('id', $excludeIds)
            ->pluck('name', 'id')
            ->all();
    }

    protected function canManageFolder(DocFolder $folder): bool
    {
        return $folder->canUserManage(Auth::user());
    }
}
