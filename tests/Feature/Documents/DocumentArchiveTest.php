<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Webkul\DocumentArchive\Filament\Actions\DownloadDocumentAction;
use Webkul\DocumentArchive\Filament\Actions\PreviewDocumentAction;
use Webkul\DocumentArchive\Filament\Pages\DocumentDashboard;
use Webkul\DocumentArchive\Filament\Pages\DocumentManager;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\EditDocFile;
use Webkul\DocumentArchive\Filament\Resources\DocFileResource\Pages\ListDocFiles;
use Webkul\DocumentArchive\Filament\Widgets\ExpiringSoonFilesWidget;
use Webkul\DocumentArchive\Filament\Widgets\RecentFilesWidget;
use Webkul\DocumentArchive\Filament\Widgets\StorageByFolderChartWidget;
use Webkul\DocumentArchive\Filament\Widgets\TopTagsChartWidget;
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
use Webkul\DocumentArchive\Services\DocumentTagService;
use Webkul\DocumentArchive\Support\FilamentUrl;
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

    expect($file->getFileSizeForHumans())->toBe(Number::fileSize(1024 * 1024));
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

it('detects expiry status for view alerts', function (): void {
    $expired = DocFile::factory()->make(['expiry_date' => now()->subDay()]);
    $expiring = DocFile::factory()->make(['expiry_date' => now()->addDays(3)]);
    $normal = DocFile::factory()->make(['expiry_date' => now()->addMonths(2)]);

    expect($expired->getExpiryStatus())->toBe('expired')
        ->and($expiring->getExpiryStatus())->toBe('expiring_soon')
        ->and($normal->getExpiryStatus())->toBeNull();
});

it('detects password protection from file or folder', function (): void {
    $folder = DocFolder::factory()->withPassword('folder-secret')->create();
    $file = DocFile::factory()->make(['folder_id' => $folder->id]);

    expect($file->isPasswordProtected())->toBeTrue();

    $protectedFile = DocFile::factory()->withPassword('file-secret')->make();

    expect($protectedFile->isPasswordProtected())->toBeTrue();
});

it('scopes expired files', function (): void {
    $before = DocFile::query()->expired()->count();

    DocFile::factory()->expired()->create();
    DocFile::factory()->create();

    expect(DocFile::query()->expired()->count())->toBe($before + 1);
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
    $expired = DocShareLink::factory()->expired()->create();
    $active = DocShareLink::factory()->create();

    Artisan::call('document-archive:cleanup-share-links');

    expect($expired->fresh()->is_active)->toBeFalse()
        ->and($active->fresh()->is_active)->toBeTrue();
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

it('stores uploaded files on the private disk via the storage service', function (): void {
    Storage::fake('private');

    $folder = DocFolder::factory()->create();
    $file = DocFile::factory()->create([
        'folder_id'         => $folder->id,
        'file_path'         => 'documents/temp/pending.pdf',
        'original_filename' => 'pending.pdf',
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

    $user = User::query()->first();
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

    $user = User::query()->first();
    test()->actingAs($user);

    $file = DocFile::factory()->withPassword('secret')->create([
        'file_path' => 'documents/test/doc.pdf',
    ]);

    Storage::disk('private')->put($file->file_path, 'content');

    test()->get(route('document-archive.preview', ['file' => $file->id]))
        ->assertOk()
        ->assertSee(__('document-archive::document-archive.password.title'));

    test()->post(route('document-archive.preview', ['file' => $file->id]), [
        'password' => 'secret',
    ])->assertRedirect(route('document-archive.preview', ['file' => $file->id]));
});

it('shows a friendly page when the stored file is missing from disk', function (): void {
    $user = User::query()->first();
    test()->actingAs($user);

    $file = DocFile::factory()->create([
        'file_path' => 'documents/missing/sample.pdf',
    ]);

    test()->get(route('document-archive.preview', ['file' => $file->id]))
        ->assertNotFound()
        ->assertSee(__('document-archive::document-archive.missing_file.title'));
});

// ─── Filament UI ──────────────────────────────────────────────────────────────

function documentArchiveFilamentUser(?User $user = null): User
{
    $user ??= User::query()->first() ?? User::factory()->create();

    foreach ([
        'view_any_document_archive_doc::file',
        'view_document_archive_doc::file',
        'create_document_archive_doc::file',
    ] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        if (! $user->can($permission)) {
            $user->givePermissionTo($permission);
        }
    }

    test()->actingAs($user);

    return $user;
}

it('renders the document manager page', function (): void {
    documentArchiveFilamentUser();

    Livewire::test(DocumentManager::class)
        ->assertSuccessful()
        ->assertSee(__('document-archive::document-archive.manager.title'))
        ->assertSee(__('document-archive::document-archive.actions.upload'));
});

it('switches document manager view modes', function (): void {
    documentArchiveFilamentUser();

    Livewire::test(DocumentManager::class)
        ->set('viewMode', 'list')
        ->assertSet('viewMode', 'list')
        ->set('viewMode', 'explorer')
        ->assertSet('viewMode', 'explorer');
});

it('lists files in the selected folder on the document manager', function (): void {
    documentArchiveFilamentUser();

    $folder = DocFolder::factory()->create();
    $file = DocFile::factory()->create([
        'folder_id' => $folder->id,
        'name'      => 'Manager Visible File',
    ]);

    Livewire::test(DocumentManager::class)
        ->call('selectFolder', $folder->id)
        ->assertSee('Manager Visible File')
        ->assertSee($file->reference_number);
});

it('restricts document listing for users without folder access', function (): void {
    $restrictedUser = User::withoutEvents(fn (): User => User::factory()->create());
    $otherUser = User::withoutEvents(fn (): User => User::factory()->create());

    Permission::query()->firstOrCreate([
        'name'       => 'view_document_archive_doc::file',
        'guard_name' => 'web',
    ]);
    $restrictedUser->givePermissionTo('view_document_archive_doc::file');

    $folder = DocFolder::factory()->private()->create();
    DocFolderPermission::create([
        'folder_id'  => $folder->id,
        'type'       => 'user',
        'user_id'    => $restrictedUser->id,
        'permission' => 'view',
    ]);

    $file = DocFile::factory()->create([
        'folder_id' => $folder->id,
        'name'      => 'Restricted Folder File',
    ]);

    $otherQuery = DocFile::query()->whereKey($file->id);
    app(DocumentAccessService::class)->applyAccessibleFilesScope($otherQuery, $otherUser);
    expect($otherQuery->exists())->toBeFalse();

    $allowedQuery = DocFile::query()->whereKey($file->id);
    app(DocumentAccessService::class)->applyAccessibleFilesScope($allowedQuery, $restrictedUser);
    expect($allowedQuery->exists())->toBeTrue();
});

it('renders arabic document manager labels', function (): void {
    documentArchiveFilamentUser();

    app()->setLocale('ar');

    Livewire::test(DocumentManager::class)
        ->assertSuccessful()
        ->assertSee(__('document-archive::document-archive.manager.title'));
});

it('renders document file list tabs', function (): void {
    documentArchiveFilamentUser();

    Livewire::test(ListDocFiles::class)
        ->assertSuccessful()
        ->assertSee(__('document-archive::document-archive.table.tabs.all'))
        ->assertSee(__('document-archive::document-archive.table.tabs.private'));
});

it('opens the expiring soon tab from the query string', function (): void {
    documentArchiveFilamentUser();

    Livewire::withQueryParams(['tab' => 'expiring_soon'])
        ->test(ListDocFiles::class)
        ->assertSet('activeTab', 'expiring_soon');
});

it('uses the configured expiring soon window', function (): void {
    config(['document-archive.expiring_soon_days' => 14]);

    DocFile::factory()->expiringSoon(10)->create();
    DocFile::factory()->expiringSoon(20)->create();

    expect(DocFile::query()->expiringSoon(14)->count())->toBe(1);
});

it('detects when preview requires the password unlock page', function (): void {
    documentArchiveFilamentUser();

    Storage::fake('private');

    $file = DocFile::factory()->withPassword('secret')->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('private')->put($file->file_path, '%PDF-1.4');

    $unlockedFile = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('private')->put($unlockedFile->file_path, '%PDF-1.4');

    expect(PreviewDocumentAction::needsPasswordPage($file))->toBeTrue()
        ->and(PreviewDocumentAction::canPreview($file))->toBeTrue()
        ->and(PreviewDocumentAction::needsPasswordPage($unlockedFile))->toBeFalse();
});

it('shows preview and download actions on the recent files widget', function (): void {
    documentArchiveFilamentUser();

    Storage::fake('private');

    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'name'      => 'Recent Widget File',
    ]);

    Storage::disk('private')->put($file->file_path, '%PDF-1.4');

    Livewire::test(RecentFilesWidget::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$file])
        ->assertTableActionExists('preview')
        ->assertTableActionExists('download');
});

it('lists only expiring soon files in the expiring widget', function (): void {
    documentArchiveFilamentUser();

    $expiring = DocFile::factory()->expiringSoon(3)->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'name'      => 'Expiring Widget File',
    ]);

    DocFile::factory()->expiringSoon(30)->create([
        'folder_id' => DocFolder::factory()->create()->id,
    ]);

    Livewire::test(ExpiringSoonFilesWidget::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$expiring])
        ->assertCountTableRecords(1);
});

it('includes the expiring widget on the dashboard when filtered', function (): void {
    documentArchiveFilamentUser();

    $dashboard = Livewire::withQueryParams(['filter' => 'expiring'])
        ->test(DocumentDashboard::class)
        ->assertSuccessful();

    expect($dashboard->instance()->getWidgets())->toContain(ExpiringSoonFilesWidget::class);
});

it('opens preview in a modal from the document manager for unlocked files', function (): void {
    documentArchiveFilamentUser();

    Storage::fake('private');

    $folder = DocFolder::factory()->create();
    $file = DocFile::factory()->create([
        'folder_id' => $folder->id,
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('private')->put($file->file_path, '%PDF-1.4');

    Livewire::test(DocumentManager::class)
        ->call('openFile', $file->id)
        ->assertActionMounted('previewDocument');
});

it('preserves locale in document archive internal urls', function (): void {
    app()->setLocale('ar');

    expect(FilamentUrl::withLocale(['filter' => 'expiring']))
        ->toHaveKey('lang', 'ar');

    expect(FilamentUrl::appendLocaleToUrl('/admin/document-archive/folders/create?parent_id=1'))
        ->toContain('lang=ar');
});

it('uses the plural model label for doc files', function (): void {
    app()->setLocale('ar');

    expect(DocFileResource::getPluralModelLabel())
        ->toBe(__('document-archive::document-archive.navigation.files.label'));
});

it('keeps preview and download visible but unavailable for orphan files', function (): void {
    documentArchiveFilamentUser();

    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'file_path' => 'documents/missing/sample.pdf',
        'name'      => 'Orphan Widget File',
    ]);

    expect(PreviewDocumentAction::hasViewAccess($file))->toBeTrue()
        ->and(PreviewDocumentAction::canPreview($file))->toBeFalse()
        ->and(DownloadDocumentAction::hasDownloadAccess($file))->toBeTrue()
        ->and(DownloadDocumentAction::canDownload($file))->toBeFalse();

    Livewire::test(RecentFilesWidget::class)
        ->assertCanSeeTableRecords([$file])
        ->assertTableActionExists('preview')
        ->assertTableActionExists('download');
});

it('uses a dedicated document archive dashboard title', function (): void {
    documentArchiveFilamentUser();

    app()->setLocale('ar');

    Livewire::test(DocumentDashboard::class)
        ->assertSee(__('document-archive::document-archive.dashboard.page_title'));
});

it('syncs tags through the document tag service', function (): void {
    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'tags'      => null,
    ]);

    app(DocumentTagService::class)->syncTags($file, [
        'tag_names' => ['HR', 'Draft'],
    ]);

    $file->refresh();

    expect($file->getTagsWithColors())->toHaveCount(2)
        ->and(collect($file->getTagsWithColors())->pluck('name')->all())->toContain('HR', 'Draft');
});

it('syncs newly created tag names with pending colors', function (): void {
    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'tags'      => null,
    ]);

    $tagService = app(DocumentTagService::class);
    $tagService->rememberNewTagColor('New Tag', '#ff0000');

    $tagService->syncTags($file, [
        'tag_names' => ['New Tag'],
    ]);

    $file->refresh();

    expect($file->getTagsWithColors()[0]['name'])->toBe('New Tag')
        ->and($file->getTagsWithColors()[0]['color'])->toBe('#ff0000');
});

it('prefers tag_names over legacy tags repeater input', function (): void {
    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'tags'      => [['name' => 'Old', 'color' => '#111111']],
    ]);

    app(DocumentTagService::class)->syncTags($file, [
        'tag_names' => ['Updated'],
        'tags'      => [['name' => 'Old', 'color' => '#111111']],
    ]);

    expect(collect($file->fresh()->getTagsWithColors())->pluck('name')->all())->toBe(['Updated']);
});

it('loads and saves tags on the edit form via the compact tag picker', function (): void {
    documentArchiveFilamentUser();

    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'tags'      => [['name' => 'Draft', 'color' => '#64748b']],
    ]);

    Livewire::test(EditDocFile::class, ['record' => $file->id])
        ->assertFormSet(['tag_names' => ['Draft']])
        ->fillForm(['tag_names' => ['Draft', 'Final']])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $file->refresh();

    expect(collect($file->getTagsWithColors())->pluck('name')->all())->toContain('Draft', 'Final');
});

it('scopes files by any selected tag name', function (): void {
    $folder = DocFolder::factory()->create();

    $draft = DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Draft', 'color' => '#64748b']],
    ]);

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Final', 'color' => '#22c55e']],
    ]);

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => null,
    ]);

    $results = DocFile::query()->withAnyTag(['Draft'])->pluck('id')->all();

    expect($results)->toBe([$draft->id]);
});

it('filters files by multiple tags using OR logic', function (): void {
    $folder = DocFolder::factory()->create();

    $draft = DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Draft', 'color' => '#64748b']],
    ]);

    $final = DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Final', 'color' => '#22c55e']],
    ]);

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Archived', 'color' => '#111111']],
    ]);

    $results = DocFile::query()->withAnyTag(['Draft', 'Final'])->pluck('id')->sort()->values()->all();

    expect($results)->toBe(collect([$draft->id, $final->id])->sort()->values()->all());
});

it('counts tag usage for dashboard charts', function (): void {
    $folder = DocFolder::factory()->create();

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'HR', 'color' => '#64748b']],
    ]);

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'HR', 'color' => '#64748b']],
    ]);

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Draft', 'color' => '#22c55e']],
    ]);

    $counts = app(DocumentTagService::class)->tagUsageCounts();

    expect($counts['HR'] ?? 0)->toBe(2)
        ->and($counts['Draft'] ?? 0)->toBe(1);
});

it('filters the files table by selected tags', function (): void {
    documentArchiveFilamentUser();

    $folder = DocFolder::factory()->create();

    $draft = DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Draft', 'color' => '#64748b']],
    ]);

    DocFile::factory()->create([
        'folder_id' => $folder->id,
        'tags'      => [['name' => 'Final', 'color' => '#22c55e']],
    ]);

    Livewire::test(ListDocFiles::class)
        ->filterTable('tags', ['Draft'])
        ->assertCanSeeTableRecords([$draft])
        ->assertCountTableRecords(1);
});

it('renders dashboard chart widgets', function (): void {
    documentArchiveFilamentUser();

    Livewire::test(TopTagsChartWidget::class)
        ->assertSuccessful()
        ->assertSee(__('document-archive::document-archive.dashboard.charts.top_tags'));

    Livewire::test(StorageByFolderChartWidget::class)
        ->assertSuccessful()
        ->assertSee(__('document-archive::document-archive.dashboard.charts.storage_by_folder'));
});

it('includes chart widgets on the document dashboard', function (): void {
    documentArchiveFilamentUser();

    $dashboard = Livewire::test(DocumentDashboard::class)->assertSuccessful();

    expect($dashboard->instance()->getWidgets())->toContain(TopTagsChartWidget::class)
        ->and($dashboard->instance()->getWidgets())->toContain(StorageByFolderChartWidget::class);
});

it('does not increment view count when previewing with embed flag', function (): void {
    documentArchiveFilamentUser();

    Storage::fake('private');

    $file = DocFile::factory()->create([
        'folder_id'  => DocFolder::factory()->create()->id,
        'extension'  => 'pdf',
        'mime_type'  => 'application/pdf',
        'view_count' => 0,
    ]);

    Storage::disk('private')->put($file->file_path, '%PDF-1.4');

    test()->get(route('document-archive.preview', ['file' => $file->id, 'embed' => 1]))
        ->assertOk();

    expect($file->fresh()->view_count)->toBe(0);
});

it('increments view count once when preview is opened intentionally', function (): void {
    documentArchiveFilamentUser();

    Storage::fake('private');

    $file = DocFile::factory()->create([
        'folder_id'  => DocFolder::factory()->create()->id,
        'extension'  => 'pdf',
        'mime_type'  => 'application/pdf',
        'view_count' => 0,
    ]);

    Storage::disk('private')->put($file->file_path, '%PDF-1.4');

    PreviewDocumentAction::recordPreviewView($file);

    expect($file->fresh()->view_count)->toBe(1);
});

it('shows enhanced list columns on the document manager page', function (): void {
    documentArchiveFilamentUser();

    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'name'      => 'Enhanced List Column File',
        'tags'      => [['name' => 'HR', 'color' => '#ff0000']],
    ]);

    Livewire::test(DocumentManager::class)
        ->set('viewMode', 'list')
        ->assertSee('Enhanced List Column File')
        ->assertSee(__('document-archive::document-archive.manager.actions.label'))
        ->assertSee(__('document-archive::document-archive.fields.expiry_date'));
});

it('exposes preview and overflow actions on dashboard tables', function (): void {
    documentArchiveFilamentUser();

    Storage::fake('private');

    $file = DocFile::factory()->create([
        'folder_id' => DocFolder::factory()->create()->id,
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'name'      => 'Dashboard Action File',
    ]);

    Storage::disk('private')->put($file->file_path, '%PDF-1.4');

    Livewire::test(RecentFilesWidget::class)
        ->assertCanSeeTableRecords([$file])
        ->assertTableActionExists('preview')
        ->assertTableActionExists('download')
        ->assertTableActionExists('previewFromName')
        ->assertTableActionExists('share');
});
