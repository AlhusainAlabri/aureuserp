<?php

namespace Webkul\DocumentArchive\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    public function attachToFile(DocFile $file, mixed $upload): void
    {
        $path = $this->resolveUploadPath($upload);

        if ($path === null) {
            return;
        }

        $disk = Storage::disk($this->disk());

        if (! $disk->exists($path)) {
            throw new \RuntimeException(__('document-archive::document-archive.validation.upload_missing'));
        }

        $originalFilename = basename($path);
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

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
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'mp4'  => 'video/mp4',
            'zip'  => 'application/zip',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
        ];

        return collect(config('document-archive.allowed_extensions', []))
            ->map(fn (string $extension): ?string => $map[strtolower($extension)] ?? null)
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
    protected function resolveUploadPath(mixed $upload): ?string
    {
        if ($upload === null || $upload === []) {
            return null;
        }

        if (is_array($upload)) {
            $upload = $upload[array_key_first($upload)] ?? null;
        }

        if ($upload instanceof TemporaryUploadedFile || $upload instanceof UploadedFile) {
            return $upload->store($this->temporaryDirectory(), $this->disk());
        }

        if (is_string($upload) && $upload !== '') {
            return $upload;
        }

        return null;
    }
}
