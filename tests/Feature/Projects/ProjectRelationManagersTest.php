<?php

use App\Filament\RelationManagers\EnhancedProjectMeetingsRelationManager;
use App\Filament\RelationManagers\ProjectDocumentsRelationManager;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\DocumentArchive\Models\DocFile;
use Webkul\DocumentArchive\Models\DocFolder;
use Webkul\Meetings\Models\Meeting;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ViewProject;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'document-archive' => 'doc_folders',
        'meetings'         => 'meetings',
        'projects'         => 'projects_projects',
    ]);
});

function projectRelationsUser(array $permissions = []): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    test()->actingAs($user);

    return $user;
}

function projectRelationsProject(): Project
{
    return Project::factory()->create([
        'company_id' => Company::query()->value('id'),
    ]);
}

it('ProjectRelationManagersTest: documents relation manager creates a project document', function (): void {
    if (! Schema::hasTable('doc_files') || ! Schema::hasColumn('doc_files', 'project_id')) {
        test()->markTestSkipped('Document archive project relation is unavailable.');
    }

    Storage::fake('private');

    $user = projectRelationsUser([
        'view_any_project_project',
        'view_project_project',
        'view_any_document_archive_doc::file',
        'view_document_archive_doc::file',
        'create_document_archive_doc::file',
    ]);
    $project = projectRelationsProject();
    $folder = DocFolder::factory()->create();
    $tempPath = 'documents/temp/project-doc.pdf';
    Storage::disk('private')->put($tempPath, '%PDF-1.4 project document');

    Livewire::test(ProjectDocumentsRelationManager::class, [
        'ownerRecord' => $project,
        'pageClass'   => ViewProject::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'name'      => 'Project Scope Document',
            'folder_id' => $folder->id,
            'upload'    => [$tempPath],
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    $document = DocFile::query()
        ->where('project_id', $project->id)
        ->where('name', 'Project Scope Document')
        ->first();

    expect($document)->not->toBeNull()
        ->and($document->creator_id)->toBe($user->id)
        ->and($document->file_path)->not->toBeNull()
        ->and(Storage::disk('private')->exists($document->file_path))->toBeTrue();
});

it('ProjectRelationManagersTest: meetings relation manager creates a project meeting', function (): void {
    if (! Schema::hasTable('meetings')) {
        test()->markTestSkipped('Meetings plugin is not installed.');
    }

    $user = projectRelationsUser([
        'view_any_project_project',
        'view_project_project',
        'view_any_meetings_meeting',
        'view_meetings_meeting',
        'create_meetings_meeting',
    ]);
    $project = projectRelationsProject();

    Livewire::test(EnhancedProjectMeetingsRelationManager::class, [
        'ownerRecord' => $project,
        'pageClass'   => ViewProject::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'title'           => 'Project Kickoff Meeting',
            'type'            => 'internal',
            'meeting_date'    => now()->addDay()->format('Y-m-d H:i'),
            'chair_person_id' => $user->id,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    $meeting = Meeting::query()
        ->where('project_id', $project->id)
        ->where('title', 'Project Kickoff Meeting')
        ->first();

    expect($meeting)->not->toBeNull()
        ->and($meeting->chair_person_id)->toBe($user->id);
});

it('ProjectRelationManagersTest: relation tab labels are translated in Arabic', function (): void {
    app()->setLocale('ar');

    expect(__('projects-extensions::project-relations.task_stages'))->toBe('مراحل المهام')
        ->and(__('projects-extensions::project-relations.documents'))->toBe('المستندات')
        ->and(__('projects-extensions::project-relations.meetings'))->toBe('الاجتماعات')
        ->and(__('projects-extensions::actions.add_document'))->toBe('إضافة مستند')
        ->and(__('projects-extensions::actions.add_meeting'))->toBe('إضافة اجتماع');
});
