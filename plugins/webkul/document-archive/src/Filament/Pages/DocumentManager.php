<?php

namespace Webkul\DocumentArchive\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\DocumentArchive\Filament\Actions\ManageDocumentTagsAction;
use Webkul\DocumentArchive\Filament\Actions\PreviewDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\ShareDocumentAction;
use Webkul\DocumentArchive\Filament\Forms\DocumentTagForm;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentStorageService;
use Webkul\DocumentArchive\Services\DocumentTagService;
use Webkul\DocumentArchive\Support\FilamentUrl;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Models\Project;

class DocumentManager extends Page
{
    use HasPageShield;

    protected static string $routePath = 'document-archive/manager';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?int $navigationSort = 60;

    protected string $view = 'document-archive::pages.document-manager';

    public ?int $currentFolderId = null;

    public ?int $selectedFileId = null;

    public string $search = '';

    public string $viewMode = 'grid';

    public bool $showFilters = false;

    public bool $includeSubfolders = false;

    public ?string $filterTag = null;

    public ?int $filterProjectId = null;

    public ?int $filterMeetingId = null;

    public ?int $filterCorrespondenceId = null;

    public ?string $filterExtension = null;

    public ?string $filterCreatedFrom = null;

    public ?string $filterCreatedTo = null;

    public ?string $filterPrivate = null;

    public array $selectedFiles = [];

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

    protected function getHeaderActions(): array
    {
        $storage = app(DocumentStorageService::class);

        return [
            Action::make('upload')
                ->label(__('document-archive::document-archive.actions.upload'))
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    FileUpload::make('upload')
                        ->label(__('document-archive::document-archive.fields.file'))
                        ->disk($storage->disk())
                        ->directory(fn (): string => $storage->temporaryDirectory())
                        ->acceptedFileTypes($storage->acceptedMimeTypes())
                        ->maxSize($storage->maxUploadSizeKilobytes())
                        ->required(),
                    TextInput::make('name')
                        ->label(__('document-archive::document-archive.fields.name')),
                    Select::make('folder_id')
                        ->label(__('document-archive::document-archive.fields.folder'))
                        ->options(fn () => DocFolder::query()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->default($this->currentFolderId)
                        ->required(),
                    ...DocumentTagForm::schema(includeAdvanced: false),
                ])
                ->action(function (array $data): void {
                    $tagService = app(DocumentTagService::class);
                    $accessService = app(DocumentAccessService::class);

                    $file = DocFile::query()->create([
                        'name'       => $data['name'] ?: null,
                        'folder_id'  => $data['folder_id'],
                        'tags'       => $accessService->normalizeTags($tagService->resolveTagsFromInput($data)),
                        'company_id' => Auth::user()?->default_company_id,
                        'creator_id' => Auth::id(),
                    ]);

                    app(DocumentStorageService::class)->attachToFile($file, $data['upload']);

                    Notification::make()
                        ->title(__('document-archive::document-archive.notifications.uploaded.title'))
                        ->body(__('document-archive::document-archive.notifications.uploaded.body', [
                            'reference' => $file->reference_number,
                        ]))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function selectFolder(?int $folderId = null): void
    {
        $this->currentFolderId = $folderId;
        $this->selectedFiles = [];
        $this->selectedFileId = null;
    }

    public function selectFile(?int $fileId = null): void
    {
        $this->selectedFileId = $fileId;
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

    public function createFolder(): void
    {
        $url = DocFolderResource::getUrl('create');

        if ($this->currentFolderId) {
            $url .= '?parent_id='.$this->currentFolderId;
        }

        $this->redirect(FilamentUrl::appendLocaleToUrl($url));
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->filterTag = null;
        $this->filterProjectId = null;
        $this->filterMeetingId = null;
        $this->filterCorrespondenceId = null;
        $this->filterExtension = null;
        $this->filterCreatedFrom = null;
        $this->filterCreatedTo = null;
        $this->filterPrivate = null;
    }

    public function getRootFoldersProperty(): EloquentCollection
    {
        return DocFolder::query()
            ->with(['children' => fn ($query) => $query->with('children')->orderBy('sort_order')->orderBy('name')])
            ->roots()
            ->ordered()
            ->get();
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

    public function getSelectedFileProperty(): ?DocFile
    {
        if (! $this->selectedFileId) {
            return null;
        }

        return DocFile::query()->with('folder')->find($this->selectedFileId);
    }

    public function getFilesProperty(): EloquentCollection
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
            $query->where('tags', 'like', '%'.$this->filterTag.'%');
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

        return $query->orderByDesc('created_at')->get();
    }

    public function getAvailableTagsProperty(): array
    {
        return array_values(app(DocumentTagService::class)->suggestions());
    }

    protected function getViewData(): array
    {
        return [
            'rootFolders'           => $this->rootFolders,
            'breadcrumbs'           => $this->breadcrumbs,
            'subfolders'            => $this->subfolders,
            'files'                 => $this->files,
            'selectedFile'          => $this->selectedFile,
            'availableTags'         => $this->availableTags,
            'projectOptions'        => (class_exists(Project::class) && DbSchema::hasTable('projects_projects'))
                ? Project::query()->pluck('name', 'id')->all()
                : [],
            'meetingOptions'        => (class_exists(Meeting::class) && DbSchema::hasTable('meetings'))
                ? Meeting::query()->pluck('title', 'id')->all()
                : [],
            'correspondenceOptions' => $this->correspondenceOptions(),
            'extensionOptions'      => DocFile::query()->distinct()->pluck('extension', 'extension')->filter()->all(),
            'expiringSoonDays'      => (int) config('document-archive.expiring_soon_days', 7),
        ];
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
}
