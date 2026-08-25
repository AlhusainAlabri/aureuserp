<?php

use App\Mail\Approvals\ApprovalWorkflowMail;
use App\Services\Approvals\ApprovalNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Enums\ApprovalStatus;
use Wezlo\FilamentApproval\Enums\StepInstanceStatus;
use Wezlo\FilamentApproval\Enums\StepType;
use Wezlo\FilamentApproval\Models\Approval;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Models\ApprovalStep;
use Wezlo\FilamentApproval\Models\ApprovalStepInstance;

it('queues approval workflow mail for assigned approvers', function (): void {
    if (! Schema::hasTable('approvals')) {
        $this->markTestSkipped('Approval tables are not installed.');
    }

    Mail::fake();

    $approver = User::factory()->create(['email' => 'approver@example.com']);
    $approvable = User::factory()->create();

    $flow = ApprovalFlow::query()->create([
        'name'            => 'Test Flow',
        'approvable_type' => User::class,
        'is_active'       => true,
    ]);

    $step = ApprovalStep::query()->create([
        'approval_flow_id'  => $flow->id,
        'name'              => 'Manager Review',
        'order'             => 1,
        'type'              => StepType::Single,
        'approver_resolver' => UserResolver::class,
        'approver_config'   => ['user_ids' => [$approver->id]],
    ]);

    $approval = Approval::query()->create([
        'approval_flow_id' => $flow->id,
        'status'           => ApprovalStatus::Pending,
        'submitted_by'     => User::factory()->create()->id,
        'submitted_at'     => now(),
        'approvable_type'  => $approvable->getMorphClass(),
        'approvable_id'    => $approvable->id,
    ]);

    $stepInstance = ApprovalStepInstance::query()->create([
        'approval_id'           => $approval->id,
        'approval_step_id'      => $step->id,
        'order'                 => 1,
        'type'                  => StepType::Single,
        'status'                => StepInstanceStatus::Waiting,
        'required_approvals'    => 1,
        'assigned_approver_ids' => [$approver->id],
        'activated_at'          => now(),
    ]);

    app(ApprovalNotificationService::class)->notifyStepApprovers($stepInstance, 'approvals.requested');

    Mail::assertQueued(ApprovalWorkflowMail::class, fn (ApprovalWorkflowMail $mail): bool => $mail->hasTo($approver->email));

    app(ApprovalNotificationService::class)->notifyStepApprovers($stepInstance->fresh(), 'approvals.requested');

    Mail::assertQueuedCount(1);
});
