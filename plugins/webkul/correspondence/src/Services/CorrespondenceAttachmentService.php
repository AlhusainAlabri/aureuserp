<?php

namespace Webkul\Correspondence\Services;

use App\Support\Media\PrivateUploadMetadata;
use Illuminate\Support\Facades\Auth;
use Webkul\Correspondence\Models\Correspondence;

class CorrespondenceAttachmentService
{
    /**
     * @param  array<int, string>|string|null  $paths
     */
    public static function storeFromPaths(Correspondence $correspondence, array|string|null $paths): void
    {
        if ($paths === null || $paths === []) {
            return;
        }

        $paths = is_array($paths) ? $paths : [$paths];

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $correspondence->attachments()->create(
                self::buildAttachmentAttributes($path),
            );
        }
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
