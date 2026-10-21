<?php

namespace Webkul\Correspondence\Services;

use App\Support\Media\PrivateUploadMetadata;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Webkul\Correspondence\Models\Correspondence;

class CorrespondenceAttachmentService
{
    /**
     * @param  array<int|string, mixed>|string|TemporaryUploadedFile|UploadedFile|null  $paths
     */
    public static function storeFromPaths(Correspondence $correspondence, mixed $paths): void
    {
        foreach (self::normalizePaths($paths) as $path) {
            $correspondence->attachments()->create(
                self::buildAttachmentAttributes($path),
            );
        }
    }

    /**
     * @param  array<int|string, mixed>|string|TemporaryUploadedFile|UploadedFile|null  $paths
     * @return array<int, string>
     */
    public static function normalizePaths(mixed $paths): array
    {
        if ($paths === null || $paths === [] || $paths === '') {
            return [];
        }

        $paths = is_array($paths) ? $paths : [$paths];
        $stored = [];

        foreach ($paths as $path) {
            $resolved = self::storeIfNeeded($path);

            if ($resolved !== null) {
                $stored[] = $resolved;
            }
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrichFileMetadata(array $data): array
    {
        return PrivateUploadMetadata::enrich($data);
    }

    /**
     * @return array{file_path: string, file_name: string, file_size: int, mime_type: string, creator_id: int|null}
     */
    protected static function buildAttachmentAttributes(string $path): array
    {
        $metadata = PrivateUploadMetadata::enrich([
            'file_path' => $path,
            'file_name' => basename($path),
            'file_size' => 0,
            'mime_type' => 'application/octet-stream',
        ]);

        return [
            'file_path'  => $metadata['file_path'],
            'file_name'  => $metadata['file_name'],
            'file_size'  => (int) ($metadata['file_size'] ?? 0),
            'mime_type'  => (string) ($metadata['mime_type'] ?? 'application/octet-stream'),
            'creator_id' => Auth::id(),
        ];
    }

    protected static function storeIfNeeded(mixed $path): ?string
    {
        if ($path instanceof TemporaryUploadedFile || $path instanceof UploadedFile) {
            return $path->store('correspondence/'.now()->year, 'private');
        }

        if (is_string($path) && $path !== '') {
            return $path;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function acceptedMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'text/csv',
            'application/zip',
        ];
    }
}
