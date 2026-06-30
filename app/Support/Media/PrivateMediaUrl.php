<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class PrivateMediaUrl
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_PREFIXES = [
        'purchases/documents/',
        'sales/',
        'invoices/documents/',
        'invoices/vendor-documents/',
    ];

    public static function downloadUrl(?string $path, int $minutes = 60): ?string
    {
        return self::url($path, 'attachment', $minutes);
    }

    public static function inlineUrl(?string $path, int $minutes = 60): ?string
    {
        return self::url($path, 'inline', $minutes);
    }

    public static function isAllowedPath(string $path): bool
    {
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected static function url(?string $path, string $disposition, int $minutes): ?string
    {
        if (blank($path) || ! self::isAllowedPath($path)) {
            return null;
        }

        $disk = Storage::disk('private');

        if (! $disk->exists($path)) {
            return null;
        }

        if (config('filesystems.disks.private.driver') === 's3') {
            try {
                return $disk->temporaryUrl($path, now()->addMinutes($minutes));
            } catch (\RuntimeException) {
                // Fall through to signed app route.
            }
        }

        return URL::temporarySignedRoute(
            'private-files.serve',
            now()->addMinutes($minutes),
            [
                'path'        => $path,
                'disposition' => $disposition,
            ],
        );
    }
}
