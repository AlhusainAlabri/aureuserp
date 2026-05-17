<?php

namespace Webkul\DocumentArchive\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Filament\Resources\DocFolderResource;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;

class DocumentManager extends Page
{
    use HasPageShield;

    protected static string $routePath = 'document-archive/manager';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?int $navigationSort = 60;

    protected string $view = 'document-archive::pages.document-manager';

    public ?int $currentFolderId = null;

    public string $search = '';

    public string $viewMode = 'grid';

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

    public function selectFolder(?int $folderId = null): void
    {
        $this->currentFolderId = $folderId;
        $this->selectedFiles = [];
    }

    public function openFile(int $fileId): void
    {
        $file = DocFile::query()->find($fileId);

        if (! $file) {
            return;
        }

        $this->redirect(route('document-archive.preview', ['file' => $file->id]));
    }

    public function uploadFile(): void
    {
        $this->redirect(DocFileResource::getUrl('create'));
    }

    public function createFolder(): void
    {
        $this->redirect(DocFolderResource::getUrl('create'));
    }

    public function getFoldersProperty(): Collection
    {
        return DocFolder::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getFilesProperty(): Collection
    {
        $query = DocFile::query()->with('folder');

        if ($this->currentFolderId) {
            $query->where('folder_id', $this->currentFolderId);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('reference_number', 'like', $term)
                    ->orWhere('original_filename', 'like', $term);
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    protected function getViewData(): array
    {
        return [
            'folders' => $this->folders,
            'files'   => $this->files,
        ];
    }
}
