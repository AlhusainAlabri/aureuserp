<?php

use App\Models\Audit\AuditEntry;
use App\Support\Audit\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Chatter\Models\Message;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);

    $this->actor = User::factory()->create();
    $this->actingAs($this->actor);
});

function makeAuditRow(): Message
{
    $component = SalaryComponent::factory()->create(['name' => 'Protected '.uniqid()]);

    return AuditRecorder::record($component, 'updated', [
        'name' => ['type' => 'modified', 'old_value' => 'a', 'new_value' => 'b'],
    ]);
}

it('AuditEntryProtectionTest: refuses to edit or delete an audit entry', function (): void {
    $row = makeAuditRow();

    expect($row)->not->toBeNull();

    foreach ([Message::find($row->id), AuditEntry::find($row->id)] as $entry) {
        expect(fn () => $entry->update(['body' => 'tampered']))->toThrow(AuthorizationException::class)
            ->and(fn () => $entry->update(['properties' => []]))->toThrow(AuthorizationException::class)
            ->and(fn () => $entry->delete())->toThrow(AuthorizationException::class);
    }

    expect(Message::find($row->id))->not->toBeNull()
        ->and(Message::find($row->id)->body)->toBe($row->body);
});

it('AuditEntryProtectionTest: still lets a log row be marked as read', function (): void {
    $row = makeAuditRow();

    $entry = Message::find($row->id);
    $entry->is_read = true;

    expect($entry->save())->toBeTrue()
        ->and((bool) Message::find($row->id)->is_read)->toBeTrue();

    $other = AuditEntry::find(makeAuditRow()->id);
    $other->forceFill(['is_read' => true])->save();

    expect((bool) AuditEntry::find($other->id)->is_read)->toBeTrue();
});

it('AuditEntryProtectionTest: leaves ordinary chatter messages editable and deletable', function (): void {
    $component = SalaryComponent::factory()->create(['name' => 'Chatty '.uniqid()]);

    $message = Message::create([
        'type'             => 'comment',
        'body'             => 'hello',
        'messageable_type' => $component->getMorphClass(),
        'messageable_id'   => $component->getKey(),
    ]);

    $message->update(['body' => 'edited']);

    expect($message->fresh()->body)->toBe('edited');

    $message->delete();

    expect(Message::find($message->id))->toBeNull();
});

it('AuditEntryProtectionTest: audit entry model only sees rows that have an event', function (): void {
    $row = makeAuditRow();

    $component = SalaryComponent::factory()->create(['name' => 'Plain '.uniqid()]);
    $plain = Message::create([
        'type'             => 'comment',
        'body'             => 'plain',
        'messageable_type' => $component->getMorphClass(),
        'messageable_id'   => $component->getKey(),
    ]);

    expect(AuditEntry::find($row->id))->not->toBeNull()
        ->and(AuditEntry::find($plain->id))->toBeNull();
});

it('AuditEntryProtectionTest: the chatter panel does not offer to pin a log row', function (): void {
    $path = view()->getFinder()->find('chatter::filament.infolists.components.messages.title-text-entry');

    expect($path)->toBe(resource_path('views/vendor/chatter/filament/infolists/components/messages/title-text-entry.blade.php'))
        ->and(file_get_contents($path))->toContain('@if (blank($getRecord()->event))');
});
