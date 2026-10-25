<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\DocumentArchive\Filament\Widgets\DocumentStatsWidget;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Services\DocumentTagService;
use Webkul\DocumentArchive\Support\DocumentDashboardCache;
use Webkul\DocumentArchive\Support\DocumentDashboardMetrics;
use Webkul\Security\Models\User;

// Unit tests are not transactional by default; these tests create files and folders that must not outlive them.
uses(DatabaseTransactions::class);

it('caches and flushes document dashboard metrics via cache version bump', function (): void {
    Cache::flush();

    $first = DocumentDashboardCache::remember('stats', fn (): int => 42);
    $second = DocumentDashboardCache::remember('stats', fn (): int => 99);

    expect($first)->toBe(42)
        ->and($second)->toBe(42);

    DocumentDashboardCache::flush();

    $third = DocumentDashboardCache::remember('stats', fn (): int => 99);

    expect($third)->toBe(99);
});

it('loads document stats widget lazily', function (): void {
    expect(DocumentStatsWidget::isLazy())->toBeTrue();
});

it('computes dashboard stats in a single query', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create());
    test()->actingAs($user);

    Cache::flush();

    $baseline = DocumentDashboardMetrics::forCurrentUser();

    $folder = DocFolder::factory()->create([
        'creator_id' => $user->id,
        'is_private' => true,
    ]);

    DocFile::factory()->count(2)->create([
        'folder_id'   => $folder->id,
        'creator_id'  => $user->id,
        'is_private'  => true,
        'expiry_date' => now()->addDays(3)->toDateString(),
    ]);

    DocumentDashboardCache::flush();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $metrics = DocumentDashboardMetrics::forCurrentUser();

    expect($metrics['total_files'])->toBe($baseline['total_files'] + 2)
        ->and($metrics['expiring_soon'])->toBe($baseline['expiring_soon'] + 2)
        ->and(count(DB::getQueryLog()))->toBe(1);

    DB::flushQueryLog();

    DocumentDashboardMetrics::forCurrentUser();

    expect(count(DB::getQueryLog()))->toBe(0);
});

it('caches document tag suggestions globally', function (): void {
    Cache::flush();

    $folder = DocFolder::factory()->create();

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Finance', 'color' => '#64748b']],
    ]);

    $service = app(DocumentTagService::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $first = $service->suggestions();

    DB::flushQueryLog();

    $second = $service->suggestions();

    expect($first)->toHaveKey('Finance')
        ->and($second)->toBe($first)
        ->and(count(DB::getQueryLog()))->toBe(0);
});

it('caches folder file counts globally', function (): void {
    Cache::flush();

    $folder = DocFolder::factory()->create();

    DocFile::factory()->count(2)->create(['folder_id' => $folder->id]);

    DocumentDashboardCache::rememberGlobal('folder_file_counts', fn (): array => [
        $folder->id => 2,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $cached = DocumentDashboardCache::rememberGlobal('folder_file_counts', fn (): array => [
        $folder->id => 99,
    ]);

    expect($cached)->toBe([$folder->id => 2])
        ->and(count(DB::getQueryLog()))->toBe(0);
});

it('does not scan the entire files table twice for tag chart counts', function (): void {
    $folder = DocFolder::factory()->create();

    DocFile::factory()->count(3)->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'HR', 'color' => '#64748b']],
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    app(DocumentTagService::class)
        ->tagUsageCountsWithColors(
            DocFile::query()->where('folder_id', $folder->id),
            10,
        );

    expect(count(DB::getQueryLog()))->toBe(1);
});

it('stores storage-by-folder cache as plain arrays', function (): void {
    Cache::flush();

    DocumentDashboardCache::remember('storage_by_folder', fn (): array => [
        ['folder_id' => 1, 'folder_name' => 'HR', 'total_bytes' => 2048],
    ]);

    $cached = DocumentDashboardCache::remember('storage_by_folder', fn (): array => []);

    expect($cached)->toBeArray()
        ->and($cached[0])->toMatchArray([
            'folder_id'   => 1,
            'folder_name' => 'HR',
            'total_bytes' => 2048,
        ]);
});
