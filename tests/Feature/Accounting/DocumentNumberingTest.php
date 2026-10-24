<?php

use App\Support\Accounting\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Move;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\Receipt;
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

it('numbers stock operations per operation type instead of by record id', function (): void {
    $table = (new Operation)->getTable();

    DB::table($table)->where('name', 'like', 'ZZ/IN/%')->delete();

    expect(DocumentSequence::nextIn($table, 'ZZ/IN'))->toBe('ZZ/IN/00001');
});

it('keeps a stock operation number once it is issued', function (): void {
    $first = Receipt::factory()->create();
    $second = Receipt::factory()->create(['operation_type_id' => $first->operation_type_id]);

    $firstName = $first->fresh()->name;
    $secondName = $second->fresh()->name;

    $first->update(['origin' => 'changed']);
    $second->update(['origin' => 'changed']);

    expect($first->fresh()->name)->toBe($firstName)
        ->and($second->fresh()->name)->toBe($secondName)
        ->and($firstName)->not->toBe($secondName)
        ->and($firstName)->toMatch('#/\d{5}$#');
});

it('numbers a document that only gets its journal while being saved', function (): void {
    $this->actingAs(User::factory()->create());

    $journal = Journal::factory()->create(['type' => 'purchase', 'code' => 'ZB'.random_int(10, 99)]);

    $move = Move::factory()->create(['journal_id' => null, 'move_type' => MoveType::IN_INVOICE, 'company_id' => $journal->company_id]);

    expect($move->fresh()->name)->not->toBeEmpty()
        ->and($move->fresh()->sequence_prefix)->not->toBeEmpty();
});
