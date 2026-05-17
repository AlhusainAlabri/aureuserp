<?php

use Webkul\Account\Models\Move;
use Webkul\Invoice\Models\Invoice;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\ApproverResolvers\UserResolver;
use Wezlo\FilamentApproval\Models\ApprovalFlow;
use Wezlo\FilamentApproval\Services\ApprovalEngine;

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function makeInvoice(): Invoice
{
    $move = Move::factory()->invoice()->create();

    return Invoice::find($move->id);
}

function makeFlow(array $userIds, int $stepCount = 3): ApprovalFlow
{
    $flow = ApprovalFlow::create([
        'name'            => 'Test Approval Flow',
        'approvable_type' => (new Invoice)->getMorphClass(),
        'is_active'       => true,
    ]);

    for ($i = 1; $i <= $stepCount; $i++) {
        $flow->steps()->create([
            'name'               => "Step {$i}",
            'order'              => $i,
            'type'               => 'single',
            'approver_resolver'  => UserResolver::class,
            'approver_config'    => ['user_ids' => $userIds],
            'required_approvals' => 1,
        ]);
    }

    return $flow->fresh('steps');
}

// ──────────────────────────────────────────────
// Flow A: full 3-step approval → posted
// ──────────────────────────────────────────────

it('approves an invoice through all 3 steps', function () {
    $approver = User::factory()->create();
    $engine = app(ApprovalEngine::class);

    $invoice = makeInvoice();
    $flow = makeFlow([$approver->id], 3);

    // Before submit
    expect($invoice->isPendingApproval())->toBeFalse()
        ->and($invoice->isApproved())->toBeFalse();

    // Submit
    $approval = $invoice->submitForApproval($flow, $approver->id);

    $invoice->refresh();
    expect($invoice->isPendingApproval())->toBeTrue()
        ->and($invoice->isApproved())->toBeFalse();

    // Step 1
    $step1 = $approval->currentStepInstance();
    expect($step1)->not->toBeNull();
    $engine->approve($step1, $approver->id);

    // Step 2
    $approval->refresh();
    $step2 = $approval->currentStepInstance();
    expect($step2)->not->toBeNull();
    $engine->approve($step2, $approver->id);

    // Step 3
    $approval->refresh();
    $step3 = $approval->currentStepInstance();
    expect($step3)->not->toBeNull();
    $engine->approve($step3, $approver->id);

    // All steps done
    $approval->refresh();
    expect($approval->currentStepInstance())->toBeNull();

    $invoice->refresh();
    expect($invoice->isApproved())->toBeTrue()
        ->and($invoice->isPendingApproval())->toBeFalse()
        ->and($invoice->canBePosted())->toBeTrue();
});

// ──────────────────────────────────────────────
// Flow B: reject at step 2 → resubmit
// ──────────────────────────────────────────────

it('stays pending after rejection and allows resubmission', function () {
    $approver = User::factory()->create();
    $engine = app(ApprovalEngine::class);

    $invoice = makeInvoice();
    $flow = makeFlow([$approver->id], 3);

    // Submit
    $approval = $invoice->submitForApproval($flow, $approver->id);

    // Approve step 1
    $step1 = $approval->currentStepInstance();
    $engine->approve($step1, $approver->id);

    // Reject step 2
    $approval->refresh();
    $step2 = $approval->currentStepInstance();
    expect($step2)->not->toBeNull();
    $engine->reject($step2, $approver->id, 'Insufficient documentation');

    // After rejection
    $invoice->refresh();
    expect($invoice->isRejected())->toBeTrue()
        ->and($invoice->isApproved())->toBeFalse()
        ->and($invoice->isPendingApproval())->toBeFalse()
        ->and($invoice->canBePosted())->toBeFalse();

    // Resubmit
    $approval2 = $invoice->submitForApproval($flow, $approver->id);
    expect($approval2->id)->not->toBe($approval->id);

    $invoice->refresh();
    // isPendingApproval() checks for a live Pending approval record — the new submission
    expect($invoice->isPendingApproval())->toBeTrue()
        ->and($invoice->isApproved())->toBeFalse();
});
