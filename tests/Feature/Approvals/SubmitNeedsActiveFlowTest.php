<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Webkul\Correspondence\Models\Correspondence;
use Webkul\Security\Models\User;
use Wezlo\FilamentApproval\Models\ApprovalFlow;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    if (! Schema::hasTable('approval_flows') || ! Schema::hasTable('correspondences')) {
        $this->markTestSkipped('Approval or correspondence tables are not installed.');
    }
});

it('hides submit-for-approval while no flow is active for the model', function (): void {
    $this->actingAs(User::factory()->create());

    ApprovalFlow::query()->where('approvable_type', (new Correspondence)->getMorphClass())->delete();

    $correspondence = new Correspondence;

    expect($correspondence->hasActiveApprovalFlow())->toBeFalse()
        ->and($correspondence->canBeSubmittedForApproval())->toBeFalse();
});

it('offers submit-for-approval once a flow is active', function (): void {
    $this->actingAs(User::factory()->create());

    ApprovalFlow::query()->create([
        'name'            => 'Letters',
        'approvable_type' => (new Correspondence)->getMorphClass(),
        'is_active'       => true,
    ]);

    expect((new Correspondence)->hasActiveApprovalFlow())->toBeTrue();
});
