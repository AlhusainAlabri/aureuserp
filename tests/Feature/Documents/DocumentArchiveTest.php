<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFileActivity;
use Webkul\DocumentArchive\Models\DocFileVersion;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Models\DocFolderPermission;
use Webkul\DocumentArchive\Models\DocShareLink;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

// Install ERP base + plugin once per process (static flag survives across tests).
beforeEach(function (): void {
    static $booted = false;

    if (! $booted) {
        if (Company::query()->doesntExist()) {
            Artisan::call('erp:install', [
                '--force'          => true,
                '--no-interaction' => true,
                '--admin-name'     => 'Test Admin',
                '--admin-email'    => 'admin@test.com',
                '--admin-password' => 'password',
            ]);
        }

        if (! Schema::hasTable('doc_folders')) {
            Artisan::call('document-archive:install', ['--no-interaction' => true]);
        }

        $booted = true;
    }
});

// ─── Reference Numbers ────────────────────────────────────────────────────────

it('auto-generates the reference number on file creation', function (): void {
    $folder = DocFolder::factory()->create();
    $file = DocFile::factory()->create(['folder_id' => $folder->id]);

    expect($file->reference_number)->toMatch('/^DOC-\d{4}-\d{4}$/');
});

it('increments the reference number sequence for the same year', function (): void {
    $folder = DocFolder::factory()->create();
    $first = DocFile::factory()->create(['folder_id' => $folder->id]);
    $second = DocFile::factory()->create(['folder_id' => $folder->id]);

    $firstSeq = (int) substr($first->reference_number, -4);
    $secondSeq = (int) substr($second->reference_number, -4);

    expect($secondSeq)->toBe($firstSeq + 1);
});

// ─── Soft Deletes ────────────────────────────────────────────────────────────

it('soft deletes a doc file', function (): void {
    $file = DocFile::factory()->create(['folder_id' => DocFolder::factory()->create()->id]);

    $file->delete();

    expect(DocFile::query()->find($file->id))->toBeNull();
    expect(DocFile::withTrashed()->find($file->id))->not->toBeNull();
});

// ─── File Helpers ─────────────────────────────────────────────────────────────

it('formats file size for humans', function (): void {
    $file = DocFile::factory()->make(['file_size' => 1024 * 1024]);

    expect($file->getFileSizeForHumans())->toBe('1 MB');
});

it('detects pdf, image, and office files', function (): void {
    $pdf = DocFile::factory()->make(['extension' => 'pdf', 'mime_type' => 'application/pdf']);
    $image = DocFile::factory()->make(['extension' => 'png', 'mime_type' => 'image/png']);
    $office = DocFile::factory()->make(['extension' => 'docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

    expect($pdf->isPdf())->toBeTrue()
        ->and($pdf->isPreviewable())->toBeTrue()
        ->and($image->isImage())->toBeTrue()
        ->and($image->isPreviewable())->toBeTrue()
        ->and($office->isOffice())->toBeTrue()
        ->and($office->isPreviewable())->toBeFalse();
});

// ─── Folder Passwords ─────────────────────────────────────────────────────────

it('checks folder password correctly', function (): void {
    $folder = DocFolder::factory()->withPassword('secret')->create();

    expect($folder->hasPassword())->toBeTrue()
        ->and($folder->checkPassword('secret'))->toBeTrue()
        ->and($folder->checkPassword('wrong'))->toBeFalse();
});

it('returns true when checking password on a folder without one', function (): void {
    $folder = DocFolder::factory()->create();

    expect($folder->hasPassword())->toBeFalse()
        ->and($folder->checkPassword('anything'))->toBeTrue();
});

// ─── Folder Tree ──────────────────────────────────────────────────────────────

it('computes folder full path and breadcrumbs', function (): void {
    $root = DocFolder::factory()->create(['name' => 'Root']);
    $child = DocFolder::factory()->create(['name' => 'Child', 'parent_id' => $root->id]);
    $grand = DocFolder::factory()->create(['name' => 'Grand', 'parent_id' => $child->id]);

    expect($grand->getFullPath())->toBe('Root / Child / Grand')
        ->and($grand->getDepth())->toBe(2)
        ->and($grand->getBreadcrumbs()->pluck('name')->all())->toBe(['Root', 'Child', 'Grand']);
});

it('returns all descendants of a folder', function (): void {
    $root = DocFolder::factory()->create();
    $a = DocFolder::factory()->create(['parent_id' => $root->id]);
    $b = DocFolder::factory()->create(['parent_id' => $a->id]);

    $descendants = $root->getAllDescendants();

    expect($descendants->pluck('id')->all())->toContain($a->id, $b->id);
});

// ─── Counters ─────────────────────────────────────────────────────────────────

it('increments view and download counters', function (): void {
    $file = DocFile::factory()->create(['view_count' => 0, 'download_count' => 0]);

    $file->incrementViewCount();
    $file->incrementDownloadCount();

    expect($file->fresh()->view_count)->toBe(1)
        ->and($file->fresh()->download_count)->toBe(1);
});

// ─── Expiry Scopes ────────────────────────────────────────────────────────────

it('scopes expiring-soon files within a window', function (): void {
    DocFile::factory()->expiringSoon(3)->create();   // within 7 days → counted
    DocFile::factory()->expiringSoon(30)->create();  // outside 7 days → excluded
    DocFile::factory()->create();                    // no expiry → excluded

    expect(DocFile::query()->expiringSoon(7)->count())->toBe(1);
});

it('scopes expired files', function (): void {
    DocFile::factory()->expired()->create();
    DocFile::factory()->create();

    expect(DocFile::query()->expired()->count())->toBe(1);
});

// ─── Share Links ──────────────────────────────────────────────────────────────

it('creates a share link with a 64-character token', function (): void {
    $link = DocShareLink::factory()->create();

    expect(strlen($link->token))->toBe(64);
});

it('validates share link expiry and active state', function (): void {
    $active = DocShareLink::factory()->create();
    $expired = DocShareLink::factory()->expired()->create();
    $inactive = DocShareLink::factory()->inactive()->create();

    expect($active->isValid())->toBeTrue()
        ->and($expired->isExpired())->toBeTrue()
        ->and($expired->isValid())->toBeFalse()
        ->and($inactive->isValid())->toBeFalse();
});

it('invalidates a view-once share link after viewing', function (): void {
    $link = DocShareLink::factory()->viewOnce()->create();

    expect($link->isValid())->toBeTrue();

    $link->markAsViewed();

    expect($link->fresh()->isValid())->toBeFalse()
        ->and($link->fresh()->view_count)->toBe(1);
});

it('exposes a public share url containing the token', function (): void {
    $link = DocShareLink::factory()->create();

    expect($link->getPublicUrl())->toContain($link->token);
});

// ─── Console Commands ─────────────────────────────────────────────────────────

it('archives expired documents via the console command', function (): void {
    $expired = DocFile::factory()->expired()->create();
    $live = DocFile::factory()->create();

    Artisan::call('document-archive:archive-expired');

    expect(DocFile::withTrashed()->find($expired->id)->deleted_at)->not->toBeNull()
        ->and(DocFile::find($live->id))->not->toBeNull();
});

it('deactivates expired share links via the cleanup command', function (): void {
    DocShareLink::factory()->expired()->create();
    DocShareLink::factory()->create();

    Artisan::call('document-archive:cleanup-share-links');

    expect(DocShareLink::query()->where('is_active', true)->count())->toBe(1);
});

// ─── Activity Log ─────────────────────────────────────────────────────────────

it('logs file activities', function (): void {
    $file = DocFile::factory()->create();

    $file->activities()->create([
        'user_id'    => $file->creator_id,
        'action'     => 'uploaded',
        'metadata'   => ['size' => $file->file_size],
        'ip_address' => '127.0.0.1',
    ]);

    expect(DocFileActivity::query()->where('file_id', $file->id)->count())->toBe(1)
        ->and($file->activities()->first()->action)->toBe('uploaded');
});

// ─── Versioning ───────────────────────────────────────────────────────────────

it('attaches versions to a file and returns the latest', function (): void {
    $file = DocFile::factory()->create();

    DocFileVersion::factory()->create(['file_id' => $file->id, 'version_number' => 1]);
    $v2 = DocFileVersion::factory()->create(['file_id' => $file->id, 'version_number' => 2]);

    expect($file->getLatestVersion()->id)->toBe($v2->id);
});

// ─── Folder Permissions ───────────────────────────────────────────────────────

it('stores folder permissions for users and roles', function (): void {
    $folder = DocFolder::factory()->create();
    $user = User::query()->first();

    DocFolderPermission::create([
        'folder_id'  => $folder->id,
        'type'       => 'user',
        'user_id'    => $user->id,
        'permission' => 'view',
    ]);

    DocFolderPermission::create([
        'folder_id'  => $folder->id,
        'type'       => 'role',
        'role_name'  => 'admin',
        'permission' => 'manage',
    ]);

    expect($folder->permissions()->count())->toBe(2);
});
