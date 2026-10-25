<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Webkul\Recruitment\Models\Applicant;
use Webkul\Recruitment\Models\Candidate;
use Webkul\Recruitment\Models\Stage;
use Webkul\Security\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    if (! Schema::hasTable('recruitments_stages')) {
        $this->markTestSkipped('Recruitment plugin tables are not installed in the test database.');
    }
});

it('puts a new applicant in the default pipeline stage', function (): void {
    $this->actingAs(User::factory()->create());

    Stage::query()->update(['is_default' => false]);
    Stage::factory()->create(['name' => 'Later', 'sort' => 5, 'is_default' => false]);
    $first = Stage::factory()->create(['name' => 'First', 'sort' => 1, 'is_default' => true]);

    $applicant = Applicant::create(['candidate_id' => Candidate::factory()->create()->id]);

    expect($applicant->stage_id)->toBe($first->id);
});

it('keeps an explicitly chosen stage', function (): void {
    $this->actingAs(User::factory()->create());

    $chosen = Stage::factory()->create(['sort' => 9, 'is_default' => false]);

    $applicant = Applicant::create([
        'candidate_id' => Candidate::factory()->create()->id,
        'stage_id'     => $chosen->id,
    ]);

    expect($applicant->stage_id)->toBe($chosen->id);
});
