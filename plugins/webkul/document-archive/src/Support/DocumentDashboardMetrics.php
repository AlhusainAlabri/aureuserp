<?php

namespace Webkul\DocumentArchive\Support;

use Illuminate\Support\Facades\Auth;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\Security\Models\User;

class DocumentDashboardMetrics
{
    /**
     * @return array{total_files: int, total_bytes: int, expiring_soon: int}
     */
    public static function forCurrentUser(?User $user = null): array
    {
        $user ??= Auth::user();

        return DocumentDashboardCache::remember('stats', fn (): array => self::compute($user));
    }

    /**
     * @return array{total_files: int, total_bytes: int, expiring_soon: int}
     */
    protected static function compute(?User $user): array
    {
        $expiringSoonDays = (int) config('document-archive.expiring_soon_days', 7);
        $today = now()->toDateString();
        $expiringEnd = now()->addDays($expiringSoonDays)->toDateString();

        $query = DocFile::query();
        app(DocumentAccessService::class)->applyAccessibleFilesScope($query, $user);

        $aggregate = $query->selectRaw(
            'COUNT(*) as total_files,
            COALESCE(SUM(file_size), 0) as total_bytes,
            SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_soon',
            [$today, $expiringEnd],
        )->first();

        return [
            'total_files'   => (int) ($aggregate->total_files ?? 0),
            'total_bytes'   => (int) ($aggregate->total_bytes ?? 0),
            'expiring_soon' => (int) ($aggregate->expiring_soon ?? 0),
        ];
    }
}
