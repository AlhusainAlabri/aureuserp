<?php

use Webkul\Recruitment\Models\CandidateSkill;

it('loads the candidate skill model without a duplicate auth import fatal error', function (): void {
    expect(class_exists(CandidateSkill::class))->toBeTrue();
    expect(new CandidateSkill)->toBeInstanceOf(CandidateSkill::class);
});
