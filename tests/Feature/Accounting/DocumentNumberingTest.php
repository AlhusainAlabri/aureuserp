<?php

use App\Support\Accounting\DocumentSequence;
use Webkul\Account\Models\Move;
use Webkul\Security\Models\User;

function moveNamed(string $name): Move
{
    $move = Move::factory()->create();
    Move::query()->whereKey($move->id)->update(['name' => $name]);

    return $move;
}

it('continues the highest number of a prefix and keeps prefixes independent', function (): void {
    moveNamed('QA/2026/00007');

    expect(DocumentSequence::next('QA/2026'))->toBe('QA/2026/00008')
        ->and(DocumentSequence::next('QA/2027'))->toBe('QA/2027/00001');

    moveNamed('QA/2026/00008');

    expect(DocumentSequence::next('QA/2026'))->toBe('QA/2026/00009');
});

it('ignores the record being numbered', function (): void {
    $move = moveNamed('QB/2026/00003');

    expect(DocumentSequence::next('QB/2026', $move->id))->toBe('QB/2026/00001');
});

it('names new moves sequentially within their journal and year', function (): void {
    $this->actingAs(User::factory()->create());

    $first = Move::factory()->create();
    $second = Move::factory()->create(['journal_id' => $first->journal_id, 'move_type' => $first->move_type, 'date' => $first->date]);

    $firstNumber = (int) last(explode('/', $first->fresh()->name));
    $secondNumber = (int) last(explode('/', $second->fresh()->name));

    expect($secondNumber)->toBe($firstNumber + 1)
        ->and($first->fresh()->name)->toMatch('#/\d{4}/\d{5}$#');
});
