<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;

class PublicMediaUrl
{
    /**
     * @return array<int, string>
     */
    public static function publicDirectories(): array
    {
        return [
            'company-logos',
            'partners/avatar',
            'employees/avatar',
            'users/avatars',
        ];
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) || str($path)->startsWith('data:')) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->temporaryUrl($path, now()->addHour());
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return array{migrated: int, skipped: int}
     */
    public static function migrateFromLocalDisk(): array
    {
        $migrated = 0;
        $skipped = 0;

        foreach (self::publicDirectories() as $directory) {
            if (! Storage::disk('local')->exists($directory)) {
                continue;
            }

            foreach (Storage::disk('local')->allFiles($directory) as $file) {
                if (Storage::disk('public')->exists($file)) {
                    $skipped++;

                    continue;
                }

                Storage::disk('public')->put(
                    $file,
                    Storage::disk('local')->get($file),
                    ['visibility' => 'public'],
                );

                $migrated++;
            }
        }

        return [
            'migrated' => $migrated,
            'skipped'  => $skipped,
        ];
    }
}
