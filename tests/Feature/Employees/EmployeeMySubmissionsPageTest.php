<?php

use Livewire\Livewire;
use Webkul\Employee\Filament\Pages\MySubmissions;
use Webkul\Employee\Models\EmployeeSubmission;
use Webkul\Security\Models\User;

require_once __DIR__.'/EmployeeTestHelpers.php';

beforeEach(function (): void {
    $this->user = createEmployeeAdminUser();
    $this->employee = createEmployeeForFlowTest($this->user, [
        'user_id' => $this->user->id,
    ]);
    $this->actingAs($this->user);
});

it('renders the my submissions page for an authenticated employee', function (): void {
    Livewire::test(MySubmissions::class)
        ->assertSuccessful()
        ->assertSee(__('employees::filament/pages/my-submissions.form.section.title'));
});

it('submits a new employee submission from the page form', function (): void {
    Livewire::test(MySubmissions::class)
        ->fillForm([
            'type'    => 'feedback',
            'subject' => 'Office temperature',
            'body'    => 'The office is too cold in the mornings.',
        ])
        ->call('submit')
        ->assertNotified();

    expect(EmployeeSubmission::query()
        ->where('employee_id', $this->employee->id)
        ->where('subject', 'Office temperature')
        ->where('status', 'open')
        ->exists())->toBeTrue();
});

it('warns when the authenticated user has no linked employee profile', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user);

    Livewire::test(MySubmissions::class)
        ->fillForm([
            'type'    => 'feedback',
            'subject' => 'Anonymous feedback',
            'body'    => 'General note.',
        ])
        ->call('submit')
        ->assertNotified();

    expect(EmployeeSubmission::query()->where('subject', 'Anonymous feedback')->exists())->toBeFalse();
});

it('opens a submission detail modal from the history list', function (): void {
    $submission = EmployeeSubmission::create([
        'employee_id' => $this->employee->id,
        'type'        => 'inquiry',
        'subject'     => 'Leave balance question',
        'body'        => 'How many days do I have left?',
        'status'      => 'open',
    ]);

    Livewire::test(MySubmissions::class)
        ->call('openSubmission', $submission->id)
        ->assertSet('viewingSubmissionId', $submission->id)
        ->assertSee('Leave balance question');
});
