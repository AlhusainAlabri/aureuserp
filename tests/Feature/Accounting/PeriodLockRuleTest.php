<?php

use App\Exceptions\PeriodLockedException;
use App\Support\Accounting\PeriodLock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Account\Enums\MoveState;
use Webkul\Chatter\Models\Message;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap();

    PeriodLock::forget();

    $this->company = Company::factory()->create();
    $this->actor = User::factory()->create();
});

afterEach(function (): void {
    PeriodLock::forget();
});

function lockCompanyAt(Company $company, ?string $date): void
{
    DB::table('companies')->where('id', $company->id)->update(['accounting_lock_date' => $date]);

    PeriodLock::forget();
}

it('PeriodLockRuleTest: decides what the lock blocks', function (
    ?string $lockDate,
    bool $withCompany,
    mixed $originalDate,
    mixed $originalState,
    mixed $newDate,
    mixed $newState,
    bool $expected,
): void {
    lockCompanyAt($this->company, $lockDate);

    $companyId = $withCompany ? $this->company->id : null;

    expect(PeriodLock::blocks($companyId, $originalDate, $originalState, $newDate, $newState))->toBe($expected);
})->with([
    'posted in the period'              => ['2026-06-30', true, '2026-06-10', 'posted', '2026-06-10', 'posted', true],
    'draft in the period'               => ['2026-06-30', true, '2026-06-10', 'draft', '2026-06-10', 'draft', false],
    'draft posted into the period'      => ['2026-06-30', true, '2026-06-10', 'draft', '2026-06-10', 'posted', true],
    'posted outside the period'         => ['2026-06-30', true, '2026-07-10', 'posted', '2026-07-12', 'posted', false],
    'posted moved into the period'      => ['2026-06-30', true, '2026-07-10', 'posted', '2026-06-10', 'posted', true],
    'posted moved out of the period'    => ['2026-06-30', true, '2026-06-10', 'posted', '2026-07-10', 'posted', true],
    'no lock date'                      => [null, true, '2026-06-10', 'posted', '2026-06-10', 'posted', false],
    'no company'                        => ['2026-06-30', false, '2026-06-10', 'posted', '2026-06-10', 'posted', false],
    'date equal to the lock date'       => ['2026-06-30', true, '2026-06-30', 'posted', '2026-06-30', 'posted', true],
    'day after the lock date'           => ['2026-06-30', true, '2026-07-01', 'posted', '2026-07-01', 'posted', false],
    'no date at all'                    => ['2026-06-30', true, null, 'posted', null, 'posted', false],
    'new record posted in the period'   => ['2026-06-30', true, null, null, '2026-06-10', 'posted', true],
    'dates as Carbon, states as enums'  => ['2026-06-30', true, CarbonImmutable::parse('2026-06-10'), MoveState::POSTED, CarbonImmutable::parse('2026-06-10'), MoveState::POSTED, true],
    'draft enum in the period'          => ['2026-06-30', true, CarbonImmutable::parse('2026-06-10'), MoveState::DRAFT, CarbonImmutable::parse('2026-06-10'), MoveState::DRAFT, false],
    'datetime string on the lock date'  => ['2026-06-30', true, '2026-06-30 23:59:59', 'posted', '2026-06-30 23:59:59', 'posted', true],
]);

it('PeriodLockRuleTest: reads the lock date of a company and memoises it until forgotten', function (): void {
    expect(PeriodLock::lockDateFor($this->company->id))->toBeNull()
        ->and(PeriodLock::lockDateFor(null))->toBeNull();

    lockCompanyAt($this->company, '2026-06-30');

    expect(PeriodLock::lockDateFor($this->company->id)->toDateString())->toBe('2026-06-30');

    DB::table('companies')->where('id', $this->company->id)->update(['accounting_lock_date' => '2026-05-31']);

    expect(PeriodLock::lockDateFor($this->company->id)->toDateString())->toBe('2026-06-30');

    PeriodLock::forget();

    expect(PeriodLock::lockDateFor($this->company->id)->toDateString())->toBe('2026-05-31');
});

it('PeriodLockRuleTest: refuses a lock date after today', function (): void {
    $tomorrow = CarbonImmutable::tomorrow();

    expect(fn () => PeriodLock::setLockDate($this->company->id, $tomorrow, $this->actor))
        ->toThrow(ValidationException::class);

    expect(DB::table('companies')->where('id', $this->company->id)->value('accounting_lock_date'))->toBeNull();
});

it('PeriodLockRuleTest: stores today and records the change once with the old and new date', function (): void {
    lockCompanyAt($this->company, '2026-06-30');

    $today = CarbonImmutable::today();

    PeriodLock::setLockDate($this->company->id, $today, $this->actor);

    expect(PeriodLock::lockDateFor($this->company->id)->toDateString())->toBe($today->toDateString());

    $entries = Message::query()
        ->where('log_name', 'audit')
        ->where('event', 'lock_date_changed')
        ->where('messageable_type', (new Company)->getMorphClass())
        ->where('messageable_id', $this->company->id)
        ->get();

    expect($entries)->toHaveCount(1);

    $entry = $entries->first();

    expect((int) $entry->causer_id)->toBe($this->actor->id)
        ->and($entry->properties['accounting_lock_date'])->toMatchArray([
            'type'      => 'modified',
            'old_value' => '2026-06-30',
            'new_value' => $today->toDateString(),
        ]);
});

it('PeriodLockRuleTest: clears the lock date and records it as removed', function (): void {
    lockCompanyAt($this->company, '2026-06-30');

    PeriodLock::setLockDate($this->company->id, null, $this->actor);

    expect(PeriodLock::lockDateFor($this->company->id))->toBeNull();

    $entry = Message::query()
        ->where('event', 'lock_date_changed')
        ->where('messageable_id', $this->company->id)
        ->latest('id')
        ->first();

    expect($entry->properties['accounting_lock_date'])->toMatchArray([
        'type'      => 'removed',
        'old_value' => '2026-06-30',
        'new_value' => null,
    ]);
});

it('PeriodLockRuleTest: writes nothing when the lock date does not change', function (): void {
    lockCompanyAt($this->company, '2026-06-30');

    PeriodLock::setLockDate($this->company->id, CarbonImmutable::parse('2026-06-30'), $this->actor);

    expect(Message::query()->where('event', 'lock_date_changed')->where('messageable_id', $this->company->id)->count())->toBe(0);
});

it('PeriodLockRuleTest: lets only holders of post_in_locked_period override', function (): void {
    Permission::findOrCreate('post_in_locked_period', 'web');

    $allowed = User::factory()->create();
    $allowed->givePermissionTo('post_in_locked_period');

    expect(PeriodLock::userMayOverride($allowed))->toBeTrue()
        ->and(PeriodLock::userMayOverride($this->actor))->toBeFalse()
        ->and(PeriodLock::userMayOverride(null))->toBeFalse();
});

it('PeriodLockRuleTest: builds a translated refusal that names the lock date', function (): void {
    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);

        $exception = PeriodLockedException::forLockDate(CarbonImmutable::parse('2026-06-30'));

        expect($exception->getMessage())->toBe(__('audit.lock.refused', ['date' => '2026-06-30']))
            ->and($exception->getMessage())->toContain('2026-06-30')
            ->and($exception->getMessage())->not->toBe('audit.lock.refused');
    }

    app()->setLocale('en');
});
