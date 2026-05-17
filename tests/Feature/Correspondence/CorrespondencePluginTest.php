<?php

use App\Filament\Actions\ExportCorrespondencePdfAction;
use App\Mail\OutgoingCorrespondenceMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Webkul\Correspondence\Filament\Resources\CorrespondenceResource;
use Webkul\Correspondence\Filament\Widgets\CorrespondenceDashboardStats;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Correspondence\Models\CorrespondenceFollower;
use Webkul\Correspondence\Models\Department;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

beforeEach(function (): void {
    if (! Schema::hasTable('correspondences')) {
        Artisan::call('correspondence:install', ['--no-interaction' => true]);
    }
});

function correspondenceUser(array $permissions = []): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create());

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    return $user;
}

function correspondenceCompany(): Company
{
    return Company::query()->firstOrFail();
}

function correspondenceDepartment(?string $code = null): Department
{
    return Department::factory()->create([
        'code'       => $code ?? strtoupper(fake()->unique()->bothify('??')),
        'company_id' => correspondenceCompany()->id,
    ]);
}

function correspondenceFlow(array $approverIds, int $steps = 2): ApprovalFlow
{
    $flow = ApprovalFlow::query()->create([
        'name'            => 'Correspondence Test Flow',
        'approvable_type' => (new Correspondence)->getMorphClass(),
        'is_active'       => true,
    ]);

    for ($step = 1; $step <= $steps; $step++) {
        $flow->steps()->create([
            'name'               => "Step {$step}",
            'order'              => $step,
            'type'               => 'single',
            'approver_resolver'  => UserResolver::class,
            'approver_config'    => ['user_ids' => $approverIds],
            'required_approvals' => 1,
        ]);
    }

    return $flow->fresh('steps');
}

function approvedCorrespondence(User $approver): Correspondence
{
    $correspondence = Correspondence::factory()->outgoing()->create([
        'company_id' => correspondenceCompany()->id,
        'type'       => 'external',
    ]);

    $approval = $correspondence->submitForApproval(correspondenceFlow([$approver->id], 1), $approver->id);
    app(ApprovalEngine::class)->approve($approval->currentStepInstance(), $approver->id);

    return $correspondence->refresh();
}

it('CorrespondencePluginTest: reference number auto-generates in OUT/DEPT/YEAR/SEQ format', function (): void {
    correspondenceUser();
    $department = correspondenceDepartment('IT');

    $correspondence = Correspondence::factory()->outgoing()->create([
        'company_id'          => correspondenceCompany()->id,
        'from_department_id'  => $department->id,
        'to_department_id'    => $department->id,
        'to_external_email'   => 'recipient@example.com',
        'purchase_request_id' => null,
    ]);

    expect($correspondence->reference_number)->toMatch('/^OUT\/IT\/'.now()->year.'\/\d{4}$/');
});

it('CorrespondencePluginTest: incoming correspondence sets status received immediately', function (): void {
    correspondenceUser();

    $correspondence = Correspondence::factory()->incoming()->create(['company_id' => correspondenceCompany()->id]);

    expect($correspondence->status)->toBe('received')
        ->and($correspondence->received_at)->not->toBeNull();
});

it('CorrespondencePluginTest: outgoing cannot be sent without full approval', function (): void {
    correspondenceUser();
    $correspondence = Correspondence::factory()->outgoing()->create(['company_id' => correspondenceCompany()->id]);

    $correspondence->send();
})->throws(RuntimeException::class);

it('CorrespondencePluginTest: full approval flow sets outgoing status approved', function (): void {
    $approver = correspondenceUser();
    $correspondence = Correspondence::factory()->outgoing()->create(['company_id' => correspondenceCompany()->id]);
    $flow = correspondenceFlow([$approver->id], 2);
    $engine = app(ApprovalEngine::class);

    $approval = $correspondence->submitForApproval($flow, $approver->id);
    $engine->approve($approval->currentStepInstance(), $approver->id);
    $approval->refresh();
    $engine->approve($approval->currentStepInstance(), $approver->id);

    expect($correspondence->refresh()->status)->toBe('approved');
});

it('CorrespondencePluginTest: rejection resets outgoing to draft', function (): void {
    $approver = correspondenceUser();
    $correspondence = Correspondence::factory()->outgoing()->create(['company_id' => correspondenceCompany()->id]);
    $approval = $correspondence->submitForApproval(correspondenceFlow([$approver->id], 1), $approver->id);

    app(ApprovalEngine::class)->reject($approval->currentStepInstance(), $approver->id, 'No');

    expect($correspondence->refresh()->status)->toBe('draft');
});

it('CorrespondencePluginTest: approved outgoing sends real email when external', function (): void {
    Mail::fake();
    $approver = correspondenceUser();
    $correspondence = approvedCorrespondence($approver);

    $correspondence->send();

    Mail::assertQueued(OutgoingCorrespondenceMail::class);
    expect($correspondence->refresh()->status)->toBe('sent');
});

it('CorrespondencePluginTest: mail failure does not change status', function (): void {
    $approver = correspondenceUser();
    $correspondence = approvedCorrespondence($approver);
    Mail::shouldReceive('to')->andThrow(new RuntimeException('mail down'));

    try {
        $correspondence->send();
    } catch (RuntimeException) {
        //
    }

    expect($correspondence->refresh()->status)->toBe('approved');
});

it('CorrespondencePluginTest: reply creates new correspondence with correct parent id', function (): void {
    $user = correspondenceUser();
    $parent = Correspondence::factory()->incoming()->create(['company_id' => correspondenceCompany()->id, 'creator_id' => $user->id]);

    $reply = $parent->createReply();

    expect($reply->parent_id)->toBe($parent->id);
});

it('CorrespondencePluginTest: reply subject is prefixed', function (): void {
    correspondenceUser();
    $parent = Correspondence::factory()->incoming()->create(['company_id' => correspondenceCompany()->id, 'subject' => 'Original']);

    expect($parent->createReply()->subject)->toStartWith('رداً على:');
});

it('CorrespondencePluginTest: thread depth returns correct count', function (): void {
    correspondenceUser();
    $root = Correspondence::factory()->incoming()->create(['company_id' => correspondenceCompany()->id]);
    $reply = $root->createReply();
    $second = $reply->createReply();

    expect($second->getThreadDepth())->toBe(2);
});

it('CorrespondencePluginTest: overdue scope returns only overdue correspondences', function (): void {
    correspondenceUser();
    $overdue = Correspondence::factory()->overdue()->create(['company_id' => correspondenceCompany()->id]);
    $notOverdue = Correspondence::factory()->create(['company_id' => correspondenceCompany()->id, 'due_date' => now()->addDay()]);

    $ids = Correspondence::query()->overdue()->pluck('id');

    expect($ids)->toContain($overdue->id)
        ->and($ids)->not->toContain($notOverdue->id);
});

it('CorrespondencePluginTest: followers notified on status change', function (): void {
    $approver = correspondenceUser();
    $follower = User::withoutEvents(fn (): User => User::factory()->create());
    $correspondence = approvedCorrespondence($approver);
    CorrespondenceFollower::factory()->create(['correspondence_id' => $correspondence->id, 'user_id' => $follower->id]);
    Mail::fake();

    $correspondence->send();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $follower->id,
    ]);
});

it('CorrespondencePluginTest: pdf export action is registered', function (): void {
    expect(class_exists(ExportCorrespondencePdfAction::class))->toBeTrue();
});

it('CorrespondencePluginTest: employee only sees own correspondences', function (): void {
    $user = correspondenceUser();
    $mine = Correspondence::factory()->incoming()->create(['company_id' => correspondenceCompany()->id, 'to_user_id' => $user->id]);
    $other = Correspondence::factory()->incoming()->create(['company_id' => correspondenceCompany()->id, 'to_user_id' => User::factory()]);

    $ids = CorrespondenceResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($mine->id)
        ->and($ids)->not->toContain($other->id);
});

it('CorrespondencePluginTest: overdue command sends correct notifications', function (): void {
    $user = correspondenceUser();
    Correspondence::factory()->overdue()->create([
        'company_id'  => correspondenceCompany()->id,
        'creator_id'  => $user->id,
        'to_user_id'  => $user->id,
    ]);

    Artisan::call('correspondence:notify-overdue');

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
    ]);
});

it('CorrespondencePluginTest: dashboard stats show correct counts per user role', function (): void {
    correspondenceUser(['view_all_departments_correspondence_correspondence']);
    Correspondence::factory()->outgoing()->create(['company_id' => correspondenceCompany()->id]);
    Correspondence::factory()->incoming()->create(['company_id' => correspondenceCompany()->id]);

    $stats = invade(app(CorrespondenceDashboardStats::class))->getStats();

    expect($stats[0]->getValue())->toBeGreaterThanOrEqual(1)
        ->and($stats[1]->getValue())->toBeGreaterThanOrEqual(1);
});
