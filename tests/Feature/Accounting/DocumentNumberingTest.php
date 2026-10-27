<?php

use App\Support\Accounting\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Webkul\Account\Enums\MoveState;
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

    $first = Move::factory()->create(['state' => MoveState::POSTED]);
    $second = Move::factory()->create(['state' => MoveState::POSTED, 'journal_id' => $first->journal_id, 'move_type' => $first->move_type, 'date' => $first->date]);

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

    $move = Move::factory()->create(['state' => MoveState::POSTED, 'journal_id' => null, 'move_type' => MoveType::IN_INVOICE, 'company_id' => $journal->company_id]);

    expect($move->fresh()->name)->not->toBeEmpty()
        ->and($move->fresh()->sequence_prefix)->not->toBeEmpty();
});

it('leaves a draft without a number and issues it when the document is posted', function (): void {
    $this->actingAs(User::factory()->create());

    $first = Move::factory()->create(['state' => MoveState::POSTED]);
    $draft = Move::factory()->create(['name' => null, 'state' => MoveState::DRAFT, 'journal_id' => $first->journal_id, 'move_type' => $first->move_type, 'date' => $first->date]);

    expect($draft->getRawOriginal('name'))->toBeNull()
        ->and($draft->fresh()->name)->toBe(__('Draft document'));

    $firstNumber = (int) last(explode('/', $first->fresh()->name));

    $draft->delete();

    $next = Move::factory()->create(['name' => null, 'state' => MoveState::DRAFT, 'journal_id' => $first->journal_id, 'move_type' => $first->move_type, 'date' => $first->date]);
    $next->update(['state' => MoveState::POSTED]);

    expect((int) last(explode('/', $next->fresh()->name)))->toBe($firstNumber + 1);
});

it('pads old-style document numbers only when asked and keeps numbers that would collide', function (): void {
    $this->actingAs(User::factory()->create());

    $old = moveNamed('PD/2026/1');
    $other = moveNamed('PD/2026/12');
    $taken = moveNamed('PE/2026/3');
    $takenPadded = moveNamed('PE/2026/00003');

    Move::query()->whereKey($other->id)->update(['payment_reference' => 'see PD/2026/1 and PD/2026/12']);

    $this->artisan('accounting:pad-document-numbers')->assertSuccessful();

    expect($old->fresh()->name)->toBe('PD/2026/1');

    $this->artisan('accounting:pad-document-numbers', ['--force' => true])->assertSuccessful();

    expect($old->fresh()->name)->toBe('PD/2026/00001')
        ->and($other->fresh()->name)->toBe('PD/2026/00012')
        ->and($other->fresh()->payment_reference)->toBe('see PD/2026/00001 and PD/2026/00012')
        ->and($taken->fresh()->name)->toBe('PE/2026/3')
        ->and($takenPadded->fresh()->name)->toBe('PE/2026/00003');
});
