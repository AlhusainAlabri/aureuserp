<?php

use Livewire\Livewire;
use Webkul\Employee\Filament\Resources\SubmissionResource\Pages\ListSubmissions;
use Webkul\Employee\Filament\Resources\SubmissionResource\Pages\ViewSubmission;
use Webkul\Employee\Models\EmployeeSubmission;

require_once __DIR__.'/../Employees/EmployeeTestHelpers.php';

it('loads the submission view page in arabic without server errors', function (): void {
    if (! class_exists(ViewSubmission::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    app()->setLocale('ar');

    $user = createEmployeeAdminUser([
        'view_any_employee_submission',
        'view_employee_submission',
    ]);

    $submission = EmployeeSubmission::query()->first();

    if ($submission === null) {
        $this->markTestSkipped('No employee submissions in database.');
    }

    $this->actingAs($user);

    Livewire::test(ViewSubmission::class, ['record' => $submission->getKey()])
        ->assertSuccessful()
        ->assertSee(__('employees::filament/resources/submission.pages.view-submission.sections.details'))
        ->assertSee(__('employees::filament/resources/submission.pages.view-submission.sections.replies'))
        ->assertSee(__('employees::filament/resources/submission.pages.view-submission.sections.quick-actions'))
        ->assertDontSee('Internal Server Error');
});

it('shows localized anonymous submitter label on the list page', function (): void {
    if (! class_exists(ListSubmissions::class)) {
        $this->markTestSkipped('Employees plugin is not installed.');
    }

    app()->setLocale('ar');

    $anonymous = EmployeeSubmission::query()
        ->where(function ($query): void {
            $query->where('is_anonymous', true)
                ->orWhere('submitter_name', 'Anonymous');
        })
        ->first();

    if ($anonymous === null) {
        $this->markTestSkipped('No anonymous submissions in database.');
    }

    $user = createEmployeeAdminUser([
        'view_any_employee_submission',
    ]);

    $this->actingAs($user);

    Livewire::test(ListSubmissions::class)
        ->assertSuccessful()
        ->assertSee(__('hr-extensions::submissions.anonymous_label'));
});
