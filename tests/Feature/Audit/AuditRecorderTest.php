<?php

use App\Support\Audit\AuditedModels;
use App\Support\Audit\AuditRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Account\Models\Move;
use Webkul\Chatter\Models\Message;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Payroll\Models\Loan;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\Payslip;
use Webkul\Payroll\Models\PayslipLine;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);

    $this->actor = User::factory()->create();
    $this->actingAs($this->actor);
});

function auditEntries(?string $event = null)
{
    return Message::query()
        ->where('log_name', 'audit')
        ->when($event, fn ($query) => $query->where('event', $event));
}

it('AuditRecorderTest: writes created updated and deleted entries with user old and new values', function (): void {
    $component = SalaryComponent::factory()->create(['name' => 'Housing', 'default_amount' => 100]);

    $created = auditEntries('created')->where('messageable_id', $component->id)
        ->where('messageable_type', $component->getMorphClass())->first();

    expect($created)->not->toBeNull()
        ->and($created->type)->toBe('notification')
        ->and($created->causer_id)->toBe($this->actor->id)
        ->and($created->properties['name']['type'])->toBe('added')
        ->and($created->properties['name']['new_value'])->toBe('Housing');

    $component->update(['name' => 'Housing allowance']);

    $updated = auditEntries('updated')->where('messageable_id', $component->id)->first();

    expect($updated)->not->toBeNull()
        ->and($updated->messageable_type)->toBe($component->getMorphClass())
        ->and($updated->causer_id)->toBe($this->actor->id)
        ->and($updated->causer_type)->toBe($this->actor->getMorphClass())
        ->and($updated->properties)->toHaveKey('name')
        ->and($updated->properties['name'])->toMatchArray([
            'type'      => 'modified',
            'old_value' => 'Housing',
            'new_value' => 'Housing allowance',
        ])
        ->and($updated->properties)->not->toHaveKey('updated_at');

    $component->delete();

    $deleted = auditEntries('deleted')->where('messageable_id', $component->id)->first();

    expect($deleted)->not->toBeNull()
        ->and($deleted->properties['name']['type'])->toBe('removed')
        ->and($deleted->properties['name']['old_value'])->toBe('Housing allowance');
});

it('AuditRecorderTest: splits soft and hard deletes and records restores', function (): void {
    $user = User::factory()->create();

    $user->delete();
    expect(auditEntries('soft_deleted')->where('messageable_id', $user->id)->count())->toBe(1);

    $user->restore();
    expect(auditEntries('restored')->where('messageable_id', $user->id)->count())->toBe(1);

    $user->forceDelete();
    expect(auditEntries('hard_deleted')->where('messageable_id', $user->id)->count())->toBe(1);
});

it('AuditRecorderTest: logs a permanent delete of an already archived record as hard deleted', function (): void {
    $user = User::factory()->create();

    $user->delete();
    $user->forceDelete();

    expect(auditEntries('soft_deleted')->where('messageable_id', $user->id)->count())->toBe(1)
        ->and(auditEntries('hard_deleted')->where('messageable_id', $user->id)->count())->toBe(1);

    $archived = User::factory()->create();
    $archived->delete();

    User::withTrashed()->find($archived->id)->forceDelete();

    expect(auditEntries('hard_deleted')->where('messageable_id', $archived->id)->count())->toBe(1);
});

it('AuditRecorderTest: logs updates to payroll batches and loans', function (): void {
    $batch = PayrollBatch::factory()->create(['reference_number' => 'PAY-AUDIT-1', 'notes' => 'Before']);
    $batch->update(['notes' => 'After']);

    $loan = Loan::factory()->create(['reference_number' => 'LOAN-AUDIT-1', 'reason' => 'Before']);
    $loan->update(['reason' => 'After']);

    $batchEntry = auditEntries('updated')->where('messageable_id', $batch->id)
        ->where('messageable_type', $batch->getMorphClass())->first();
    $loanEntry = auditEntries('updated')->where('messageable_id', $loan->id)
        ->where('messageable_type', $loan->getMorphClass())->first();

    expect(AuditedModels::classes())->toContain(PayrollBatch::class, Loan::class)
        ->and($batchEntry->properties['notes'])->toMatchArray(['old_value' => 'Before', 'new_value' => 'After'])
        ->and($loanEntry->properties['reason'])->toMatchArray(['old_value' => 'Before', 'new_value' => 'After']);
});

it('AuditRecorderTest: skips an update with no loggable change', function (): void {
    $rule = SalaryComponent::factory()->create();

    $before = auditEntries('updated')->count();

    $rule->touch();
    $rule->update(['name' => $rule->name]);

    expect(auditEntries('updated')->count())->toBe($before);
});

it('AuditRecorderTest: masks sensitive attributes', function (): void {
    $user = User::factory()->create();

    $user->update(['password' => Hash::make('a-brand-new-secret')]);

    $entry = auditEntries('updated')->where('messageable_id', $user->id)
        ->where('messageable_type', $user->getMorphClass())->first();

    expect($entry)->not->toBeNull()
        ->and($entry->properties['password']['old_value'])->toBe('***')
        ->and($entry->properties['password']['new_value'])->toBe('***');

    $user->forceFill(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP'])->save();

    $secret = auditEntries('updated')->where('messageable_id', $user->id)->latest('id')->first();

    expect($secret->properties['app_authentication_secret']['new_value'])->toBe('***');

    $creation = auditEntries('created')->where('messageable_id', $user->id)
        ->where('messageable_type', $user->getMorphClass())->first();

    expect($creation->properties['password']['new_value'])->toBe('***');
});

it('AuditRecorderTest: ignores the remember token', function (): void {
    $user = User::factory()->create();

    $user->update(['remember_token' => 'rotated-token']);

    expect(auditEntries('updated')->where('messageable_id', $user->id)
        ->where('messageable_type', $user->getMorphClass())->count())->toBe(0);
});

it('AuditRecorderTest: writes nothing without a signed-in user', function (): void {
    Auth::guard()->logout();

    $component = SalaryComponent::factory()->create();
    $component->update(['name' => 'Changed by the system']);
    $component->delete();

    expect(auditEntries()->where('messageable_type', $component->getMorphClass())
        ->where('messageable_id', $component->id)->count())->toBe(0);
});

it('AuditRecorderTest: writes with an explicit causer even when nobody is signed in', function (): void {
    Auth::guard()->logout();

    $subject = User::factory()->create();

    $message = AuditRecorder::record($subject, 'login', [], $subject);

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->event)->toBe('login')
        ->and($message->log_name)->toBe('audit')
        ->and($message->causer_id)->toBe($subject->id)
        ->and($message->messageable_id)->toBe($subject->id);
});

it('AuditRecorderTest: returns null when record is called without any causer', function (): void {
    Auth::guard()->logout();

    expect(AuditRecorder::record(SalaryComponent::factory()->make(['id' => 1]), 'updated', []))->toBeNull();
});

it('AuditRecorderTest: takes the company from the causer default company', function (): void {
    $companyId = Company::query()->value('id');
    $this->actor->forceFill(['default_company_id' => $companyId])->saveQuietly();

    $component = SalaryComponent::factory()->create();

    $entry = auditEntries('created')->where('messageable_id', $component->id)
        ->where('messageable_type', $component->getMorphClass())->first();

    expect($entry->company_id)->toBe($companyId);
});

it('AuditRecorderTest: does not log a model that already uses HasLogActivity twice', function (): void {
    expect(in_array(HasLogActivity::class, class_uses_recursive(Move::class), true))->toBeTrue();

    $classes = AuditedModels::classes();

    expect($classes)->toContain(SalaryComponent::class, PasiRule::class, Payslip::class, User::class);

    foreach ($classes as $class) {
        expect(class_uses_recursive($class))->not->toContain(HasLogActivity::class);
    }

    $filtered = (new class extends AuditedModels
    {
        protected static function candidates(): array
        {
            return [Move::class, SalaryComponent::class, 'App\\Does\\Not\\Exist'];
        }
    })::classes();

    expect($filtered)->toBe([SalaryComponent::class]);
});

it('AuditRecorderTest: never breaks the save when writing fails', function (): void {
    $fail = new stdClass;
    $fail->on = true;

    // A Message listener cannot be removed again, so it is switched off once the test is done.
    Message::creating(function () use ($fail): void {
        if ($fail->on) {
            throw new RuntimeException('audit write failed');
        }
    });

    try {
        $component = SalaryComponent::factory()->create(['name' => 'Before']);
        $component->update(['name' => 'After']);
    } finally {
        $fail->on = false;
    }

    expect($component->fresh()->name)->toBe('After')
        ->and(auditEntries()->where('messageable_id', $component->id)
            ->where('messageable_type', $component->getMorphClass())->count())->toBe(0);
});

it('AuditRecorderTest: logs a large payslip batch quickly', function (): void {
    $payslip = Payslip::factory()->create();
    $component = SalaryComponent::factory()->create();

    $started = microtime(true);

    for ($i = 0; $i < 300; $i++) {
        PayslipLine::factory()->create(['payslip_id' => $payslip->id, 'component_id' => $component->id]);
    }

    $elapsed = microtime(true) - $started;

    expect(auditEntries('created')->where('messageable_type', (new PayslipLine)->getMorphClass())->count())->toBe(300)
        ->and($elapsed)->toBeLessThan(10.0);
});
