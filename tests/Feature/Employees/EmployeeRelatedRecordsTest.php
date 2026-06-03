<?php

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Webkul\Employee\Enums\ResumeDisplayType;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ManageDocuments;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ManageResume;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ManageSkill;
use Webkul\Employee\Filament\Resources\EmployeeResource\Pages\ManageWarnings;
use Webkul\Employee\Mail\EmployeeWarningMail;
use Webkul\Employee\Models\EmployeeDocument;
use Webkul\Employee\Models\EmployeeResume;
use Webkul\Employee\Models\EmployeeSkill;
use Webkul\Employee\Models\EmployeeWarning;
use Webkul\Employee\Models\WarningType;

require_once __DIR__.'/EmployeeTestHelpers.php';

beforeEach(function (): void {
    $this->user = createEmployeeAdminUser();
    $this->employee = createEmployeeForFlowTest($this->user, [
        'work_email' => 'warnings@example.com',
    ]);
    $this->actingAs($this->user);
});

it('creates an employee document and serves a preview response', function (): void {
    Storage::fake('local');

    $path = "employees/{$this->employee->id}/documents/passport.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 employee passport');

    $document = EmployeeDocument::create([
        'employee_id'   => $this->employee->id,
        'document_type' => 'passport',
        'document_name' => 'Employee Passport',
        'file_path'     => $path,
        'expiry_date'   => now()->addYear()->toDateString(),
        'creator_id'    => $this->user->id,
    ]);

    expect(Storage::disk('local')->exists($document->file_path))->toBeTrue();

    $this->get(route('employees.documents.preview', $document))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf');

    Livewire::test(ManageDocuments::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$document])
        ->callAction(TestAction::make('view')->table($document))
        ->assertSuccessful();
});

it('creates a warning and emails the employee automatically', function (): void {
    Mail::fake();

    Livewire::test(ManageWarnings::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->callAction(TestAction::make('create')->table(), [
            'subject'         => 'Repeated tardiness',
            'issued_at'       => now()->toDateString(),
            'is_acknowledged' => false,
        ])
        ->assertNotified();

    $warning = EmployeeWarning::query()
        ->where('employee_id', $this->employee->id)
        ->where('subject', 'Repeated tardiness')
        ->firstOrFail();

    Mail::assertSent(EmployeeWarningMail::class, fn (EmployeeWarningMail $mail): bool => $mail->warning->is($warning));
});

it('can resend a warning email from the table action', function (): void {
    Mail::fake();

    $warningType = WarningType::create([
        'name'       => 'Conduct',
        'creator_id' => $this->user->id,
    ]);

    $warning = EmployeeWarning::create([
        'employee_id'     => $this->employee->id,
        'warning_type_id' => $warningType->id,
        'subject'         => 'Resend me',
        'issued_at'       => now(),
        'is_acknowledged' => false,
        'creator_id'      => $this->user->id,
    ]);

    Livewire::test(ManageWarnings::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->callAction(TestAction::make('send')->table($warning))
        ->assertNotified();

    Mail::assertSent(EmployeeWarningMail::class, fn (EmployeeWarningMail $mail): bool => $mail->warning->is($warning));
});

it('adds a skill to an employee from the skills page', function (): void {
    ['skillType' => $skillType, 'skill' => $skill, 'skillLevel' => $skillLevel] = createSkillCatalog($this->user);

    Livewire::test(ManageSkill::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->callAction(TestAction::make('create')->table(), [
            'skill_type_id'  => $skillType->id,
            'skill_id'       => $skill->id,
            'skill_level_id' => $skillLevel->id,
        ])
        ->assertNotified();

    expect(EmployeeSkill::query()
        ->where('employee_id', $this->employee->id)
        ->where('skill_id', $skill->id)
        ->exists())->toBeTrue();
});

it('adds a resume entry from the resume page', function (): void {
    $resumeType = createResumeLineType($this->user);

    Livewire::test(ManageResume::class, ['record' => $this->employee->id])
        ->assertSuccessful()
        ->callAction(TestAction::make('create')->table(), [
            'name'         => 'Senior Developer at ACME',
            'type'         => $resumeType->id,
            'start_date'   => now()->subYears(2)->toDateString(),
            'display_type' => ResumeDisplayType::Classic->value,
            'description'  => 'Built internal ERP modules.',
        ])
        ->assertNotified();

    expect(EmployeeResume::query()
        ->where('employee_id', $this->employee->id)
        ->where('name', 'Senior Developer at ACME')
        ->exists())->toBeTrue();
});
