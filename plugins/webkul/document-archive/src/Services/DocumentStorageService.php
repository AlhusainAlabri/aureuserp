<?php

namespace Webkul\DocumentArchive\Services;

use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFileVersion;
use Webkul\DocumentArchive\Models\DocFolder;

class DocumentStorageService
{
    public function disk(): string
    {
        return (string) config('document-archive.storage_disk', 'private');
    }

    /**
     * @param  TemporaryUploadedFile|UploadedFile|array<int, string>|string|null  $upload
     */
    public function resolveDisplayName(mixed $upload, ?string $customName = null): string
    {
        if (filled($customName)) {
            return $customName;
        }

        $originalFilename = $this->resolveOriginalFilename($upload);

        if ($originalFilename === null) {
            return '';
        }

        return pathinfo($originalFilename, PATHINFO_FILENAME);
    }

    public function configureUploadField(FileUpload $field, bool $required = true): FileUpload
    {
        $field
            ->disk($this->disk())
            ->directory(fn (): string => $this->temporaryDirectory())
            ->visibility('private')
            ->acceptedFileTypes($this->acceptedMimeTypes())
            ->maxSize($this->maxUploadSizeKilobytes())
            ->storeFiles(false)
            ->fetchFileInformation(false);

        if ($required) {
            $field->required();
        }

        return $field;
    }

    /**
     * @return array{original_filename: string, file_path: string, mime_type: string, extension: string}
     */
    public function pendingFileAttributes(?string $originalFilename = null): array
    {
        return [
            'original_filename' => filled($originalFilename) ? $originalFilename : 'document',
            'file_path'         => '',
            'mime_type'         => 'application/octet-stream',
            'extension'         => '',
        ];
    }

    /**
     * @param  TemporaryUploadedFile|UploadedFile|array<int, string>|string|null  $upload
     */
    public function attachToFile(DocFile $file, mixed $upload): void
    {
        $originalFilename = $this->resolveOriginalFilename($upload);
        $path = $this->resolveUploadPath($upload);

        if ($path === null) {
            throw new \RuntimeException(__('document-archive::document-archive.validation.upload_missing'));
        }

        $disk = Storage::disk($this->disk());

        if (! $disk->exists($path)) {
            throw new \RuntimeException(__('document-archive::document-archive.validation.upload_missing'));
        }

        $originalFilename ??= basename($path);
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION) ?: pathinfo($path, PATHINFO_EXTENSION));

        $this->validateExtension($extension);

        $size = $disk->size($path);

        $this->validateSize($size);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        $destination = $this->buildPath($file, $extension);

        if ($path !== $destination) {
            $disk->move($path, $destination);
        }

        $file->forceFill([
            'original_filename' => $originalFilename,
            'file_path'         => $destination,
            'file_size'         => $size,
            'mime_type'         => $mimeType,
            'extension'         => $extension,
            'name'              => $file->name ?: pathinfo($originalFilename, PATHINFO_FILENAME),
        ])->save();

        DocFileVersion::query()->create([
            'file_id'           => $file->id,
            'version_number'    => $file->version ?? 1,
            'file_path'         => $destination,
            'file_size'         => $size,
            'original_filename' => $originalFilename,
            'change_note'       => __('document-archive::document-archive.activity.uploaded'),
            'creator_id'        => Auth::id() ?? $file->creator_id,
        ]);

        $file->activities()->create([
            'user_id' => Auth::id() ?? $file->creator_id,
            'action'  => 'uploaded',
        ]);
    }

    public function replaceFile(DocFile $file, mixed $upload): void
    {
        $oldPath = $file->file_path;

        $this->attachToFile($file, $upload);

        if ($oldPath && $oldPath !== $file->file_path) {
            Storage::disk($this->disk())->delete($oldPath);
        }

        $file->increment('version');
    }

    public function validateExtension(string $extension): void
    {
        $allowed = config('document-archive.allowed_extensions', []);

        if (! in_array(strtolower($extension), $allowed, true)) {
            throw new \InvalidArgumentException(__('document-archive::document-archive.validation.invalid_extension'));
        }
    }

    public function validateSize(int $bytes): void
    {
        $maxMb = (int) config('document-archive.max_file_size_mb', 50);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($bytes > $maxBytes) {
            throw new \InvalidArgumentException(__('document-archive::document-archive.validation.file_too_large', [
                'max' => $maxMb,
            ]));
        }
    }

    public function maxUploadSizeKilobytes(): int
    {
        return (int) config('document-archive.max_file_size_mb', 50) * 1024;
    }

    /**
     * @return array<int, string>
     */
    public function acceptedMimeTypes(): array
    {
        $map = [
            'pdf'  => [
                'application/pdf',
                'application/x-pdf',
            ],
            'doc'  => [
                'application/msword',
                'application/x-msword',
            ],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
            'xls'  => [
                'application/vnd.ms-excel',
                'application/msexcel',
            ],
            'xlsx' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
            ],
            'ppt'  => [
                'application/vnd.ms-powerpoint',
                'application/mspowerpoint',
            ],
            'pptx' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
            ],
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'mp4'  => 'video/mp4',
            'zip'  => [
                'application/zip',
                'application/x-zip-compressed',
            ],
            'txt'  => 'text/plain',
            'csv'  => [
                'text/csv',
                'text/plain',
                'application/csv',
            ],
        ];

        return collect(config('document-archive.allowed_extensions', []))
            ->flatMap(function (string $extension) use ($map): array {
                $types = $map[strtolower($extension)] ?? null;

                if ($types === null) {
                    return [];
                }

                return is_array($types) ? $types : [$types];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function buildPath(DocFile $file, string $extension): string
    {
        $companyId = $file->company_id ?? 'general';
        $folderSegment = 'root';

        if ($file->folder_id) {
            $folder = DocFolder::query()->find($file->folder_id);
            $folderSegment = $folder?->slug ?? 'folder-'.$file->folder_id;
        }

        $reference = $file->reference_number ?? Str::uuid()->toString();

        return "documents/{$companyId}/{$folderSegment}/{$reference}.{$extension}";
    }

    public function temporaryDirectory(): string
    {
        return 'documents/temp/'.now()->format('Y/m');
    }

    public function fileExists(DocFile $file): bool
    {
        if (blank($file->file_path)) {
            return false;
        }

        return Storage::disk($this->disk())->exists($file->file_path);
    }

    /**
     * @param  TemporaryUploadedFile|UploadedFile|array<int, string>|string|null  $upload
     */
    protected function resolveOriginalFilename(mixed $upload): ?string
    {
        if ($upload === null || $upload === []) {
            return null;
        }

        if (is_array($upload)) {
            $upload = $upload[array_key_first($upload)] ?? null;
        }

        if ($upload instanceof TemporaryUploadedFile || $upload instanceof UploadedFile) {
            return $upload->getClientOriginalName();
        }

        if (is_string($upload) && $upload !== '') {
            return basename($upload);
        }

        return null;
    }

    /**
     * @param  TemporaryUploadedFile|UploadedFile|array<int, string>|string|null  $upload
     */
    protected function resolveUploadPath(mixed $upload): ?string
    {
        if ($upload === null || $upload === []) {
            return null;
        }

        if (is_array($upload)) {
            $upload = $upload[array_key_first($upload)] ?? null;
        }

        if ($upload instanceof TemporaryUploadedFile || $upload instanceof UploadedFile) {
            if ($upload instanceof TemporaryUploadedFile && ! $upload->exists()) {
                return null;
            }

            return $upload->store($this->temporaryDirectory(), $this->disk());
        }

        if (! is_string($upload) || $upload === '') {
            return null;
        }

        $disk = Storage::disk($this->disk());

        if ($disk->exists($upload)) {
            return $upload;
        }

        $livewireDisk = FileUploadConfiguration::disk();

        if ($livewireDisk !== $this->disk() && Storage::disk($livewireDisk)->exists($upload)) {
            $destination = $this->temporaryDirectory().'/'.basename($upload);
            $disk->put($destination, Storage::disk($livewireDisk)->get($upload));

            return $destination;
        }

        return null;
    }
}
