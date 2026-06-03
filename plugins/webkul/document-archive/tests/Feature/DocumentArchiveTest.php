<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Webkul\DocumentArchive\Mail\DocumentShareMail;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFileActivity;
use Webkul\DocumentArchive\Models\DocFileVersion;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\DocumentArchive\Models\DocFolderPermission;
use Webkul\DocumentArchive\Models\DocShareLink;
use Webkul\DocumentArchive\Services\DocumentAccessService;
use Webkul\DocumentArchive\Services\DocumentShareService;
use Webkul\DocumentArchive\Services\DocumentStorageService;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

require_once __DIR__.'/../../../support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function (): void {
    TestBootstrapHelper::ensureERPInstalled();

    if (! Schema::hasTable('doc_folders')) {
        Artisan::call('migrate', [
            '--path'     => 'plugins/webkul/document-archive/database/migrations',
            '--realpath' => false,
            '--force'    => true,
        ]);
    }
});

function archiveCompany(): Company
{
    return Company::query()->first() ?? Company::factory()->create();
}

function archiveUser(): User
{
    return User::query()->first() ?? User::factory()->create();
}

it('auto-generates the reference number on file creation', function (): void {
    $folder = DocFolder::factory()->create([
        'company_id' => archiveCompany()->id,
        'creator_id' => archiveUser()->id,
    ]);

    $file = DocFile::factory()->create([
        'folder_id'  => $folder->id,
        'company_id' => $folder->company_id,
        'creator_id' => $folder->creator_id,
    ]);

    expect($file->reference_number)->toMatch('/^DOC-\d{4}-\d{4}$/');
});

it('increments the reference number sequence for the same year', function (): void {
    $folder = DocFolder::factory()->create([
        'company_id' => archiveCompany()->id,
        'creator_id' => archiveUser()->id,
    ]);

    $first = DocFile::factory()->create(['folder_id' => $folder->id]);
    $second = DocFile::factory()->create(['folder_id' => $folder->id]);

    $firstSeq = (int) substr($first->reference_number, -4);
    $secondSeq = (int) substr($second->reference_number, -4);

    expect($secondSeq)->toBe($firstSeq + 1);
});

it('soft deletes a doc file', function (): void {
    $file = DocFile::factory()->create([
        'folder_id'  => DocFolder::factory()->create()->id,
    ]);

    $file->delete();

    expect(DocFile::query()->find($file->id))->toBeNull();
    expect(DocFile::withTrashed()->find($file->id))->not->toBeNull();
});

it('formats file size for humans', function (): void {
    $file = DocFile::factory()->make(['file_size' => 1024 * 1024]);

    expect($file->getFileSizeForHumans())->toBe(Number::fileSize(1024 * 1024));
});

it('detects pdf and image files', function (): void {
    $pdf = DocFile::factory()->make([
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ]);
    $image = DocFile::factory()->make([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);
    $office = DocFile::factory()->make([
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);

    expect($pdf->isPdf())->toBeTrue()
        ->and($pdf->isPreviewable())->toBeTrue()
        ->and($image->isImage())->toBeTrue()
        ->and($image->isPreviewable())->toBeTrue()
        ->and($office->isOffice())->toBeTrue()
        ->and($office->isPreviewable())->toBeFalse();
});

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

it('computes folder full path with breadcrumbs', function (): void {
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

it('increments view and download counters', function (): void {
    $file = DocFile::factory()->create(['view_count' => 0, 'download_count' => 0]);

    $file->incrementViewCount();
    $file->incrementDownloadCount();

    expect($file->fresh()->view_count)->toBe(1)
        ->and($file->fresh()->download_count)->toBe(1);
});

it('scopes expiring soon files within a window', function (): void {
    DocFile::factory()->expiringSoon(3)->create();
    DocFile::factory()->expiringSoon(30)->create();
    DocFile::factory()->create();

    expect(DocFile::query()->expiringSoon(7)->count())->toBe(1);
});

it('scopes expired files', function (): void {
    DocFile::factory()->expired()->create();
    DocFile::factory()->create();

    expect(DocFile::query()->expired()->count())->toBe(1);
});

it('creates a share link with a 64 character token', function (): void {
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

it('exposes a public share url', function (): void {
    $link = DocShareLink::factory()->create();

    expect($link->getPublicUrl())->toContain($link->token);
});

it('archives expired documents via the console command', function (): void {
    DocFile::factory()->expired()->create();
    DocFile::factory()->create();

    Artisan::call('document-archive:archive-expired');

    expect(DocFile::query()->count())->toBe(1)
        ->and(DocFile::withTrashed()->count())->toBe(2);
});

it('deactivates expired share links via the cleanup command', function (): void {
    DocShareLink::factory()->expired()->create();
    DocShareLink::factory()->create();

    Artisan::call('document-archive:cleanup-share-links');

    expect(DocShareLink::query()->where('is_active', true)->count())->toBe(1);
});

it('logs file activities polymorphically', function (): void {
    $file = DocFile::factory()->create();

    $file->activities()->create([
        'user_id'    => $file->creator_id,
        'action'     => 'uploaded',
        'metadata'   => ['size' => $file->file_size],
        'ip_address' => '127.0.0.1',
    ]);

    expect(DocFileActivity::query()->count())->toBe(1)
        ->and($file->activities()->first()->action)->toBe('uploaded');
});

it('attaches versions to a doc file and returns the latest', function (): void {
    $file = DocFile::factory()->create();

    DocFileVersion::factory()->create(['file_id' => $file->id, 'version_number' => 1]);
    $v2 = DocFileVersion::factory()->create(['file_id' => $file->id, 'version_number' => 2]);

    expect($file->getLatestVersion()->id)->toBe($v2->id);
});

it('stores folder permissions for users and roles', function (): void {
    $folder = DocFolder::factory()->create();
    $user = archiveUser();

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

it('stores uploaded files on the private disk via the storage service', function (): void {
    Storage::fake('private');

    $folder = DocFolder::factory()->create([
        'company_id' => archiveCompany()->id,
        'creator_id' => archiveUser()->id,
    ]);

    $file = DocFile::factory()->create([
        'folder_id'  => $folder->id,
        'company_id' => $folder->company_id,
        'creator_id' => $folder->creator_id,
        'file_path'  => null,
    ]);

    $tempPath = 'documents/temp/sample.pdf';
    Storage::disk('private')->put($tempPath, '%PDF-1.4 test content');

    app(DocumentStorageService::class)->attachToFile($file, $tempPath);

    $file->refresh();

    expect($file->file_path)->toContain('documents/')
        ->and($file->extension)->toBe('pdf')
        ->and(Storage::disk('private')->exists($file->file_path))->toBeTrue()
        ->and($file->versions()->count())->toBe(1);
});

it('normalizes legacy and structured document tags', function (): void {
    $normalized = app(DocumentAccessService::class)->normalizeTags([
        'draft',
        ['name' => 'final', 'color' => '#ff0000'],
    ]);

    expect($normalized)->toHaveCount(2)
        ->and($normalized[0]['name'])->toBe('draft')
        ->and($normalized[1]['color'])->toBe('#ff0000');
});

it('returns tags with colors from structured tag data', function (): void {
    $file = DocFile::factory()->make([
        'tags' => [
            ['name' => 'HR', 'color' => '#abc'],
        ],
    ]);

    expect($file->getTagsWithColors()[0]['name'])->toBe('HR')
        ->and($file->getTagsWithColors()[0]['color'])->toBe('#abc');
});

it('creates a share link and queues notification mail', function (): void {
    Mail::fake();

    $user = archiveUser();
    test()->actingAs($user);

    $file = DocFile::factory()->create([
        'folder_id'  => DocFolder::factory()->create()->id,
        'creator_id' => $user->id,
    ]);

    $link = app(DocumentShareService::class)->createLink($file, [
        'shared_with_email' => 'recipient@example.com',
        'view_once'         => true,
    ]);

    expect($link->view_once)->toBeTrue()
        ->and($link->shared_with_email)->toBe('recipient@example.com');

    Mail::assertQueued(DocumentShareMail::class);
});

it('blocks preview until the document password is unlocked', function (): void {
    Storage::fake('private');

    $file = DocFile::factory()->withPassword('secret')->create([
        'file_path' => 'documents/test/doc.pdf',
    ]);

    Storage::disk('private')->put($file->file_path, 'content');

    $this->get(route('document-archive.preview', ['file' => $file->id]))
        ->assertOk()
        ->assertSee(__('document-archive::document-archive.password.title'));

    $this->post(route('document-archive.preview', ['file' => $file->id]), [
        'password' => 'secret',
    ])->assertRedirect(route('document-archive.preview', ['file' => $file->id]));
});
