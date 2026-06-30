<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;

class PrivateUploadMetadata
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrich(array $data, string $disk = 'private'): array
    {
        $filePath = $data['file_path'] ?? null;

        if (blank($filePath)) {
            return $data;
        }

        if (is_array($filePath)) {
            $filePath = $filePath[array_key_first($filePath)] ?? null;
        }

        if (! is_string($filePath) || $filePath === '') {
            return $data;
        }

        $data['file_path'] = $filePath;
        $data['file_name'] ??= basename($filePath);

        $storage = Storage::disk($disk);

        if ($storage->exists($filePath)) {
            $data['file_size'] = $storage->size($filePath);
            $data['mime_type'] = $storage->mimeType($filePath) ?: 'application/octet-stream';
        }

        if (empty($data['title'])) {
            $data['title'] = $data['file_name'];
        }

        return $data;
    }
}
