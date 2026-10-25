<?php

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Helpers\FeatureTestBootstrap;
use Tests\Helpers\PasiFixtures;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Services\PasiRuleManager;
use Webkul\Payroll\Services\PasiRuleResolver;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    PasiFixtures::clearRules();
});

it('PasiRulesTest: categorises an employee by nationality against the company country', function (): void {
    $company = PasiFixtures::company('OM');
    $resolver = app(PasiRuleResolver::class);

    expect($resolver->categoryFor(PasiFixtures::employee($company, 'OM')))->toBe(PasiCategory::Omani)
        ->and($resolver->categoryFor(PasiFixtures::employee($company, 'IN')))->toBe(PasiCategory::Expat)
        ->and($resolver->categoryFor(PasiFixtures::employee($company, null)))->toBeNull()
        ->and($resolver->categoryFor(PasiFixtures::employee(PasiFixtures::company(null), 'OM')))->toBeNull();
});

it('PasiRulesTest: finds the rule in force including both boundary days', function (): void {
    $omani = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01', '2026-12-31');
    PasiFixtures::rule(PasiCategory::Expat, 0, 2, null, '2026-01-01', '2026-12-31');

    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM');
    $resolver = app(PasiRuleResolver::class);

    expect($resolver->ruleFor($employee, Carbon::create(2026, 1, 1))?->id)->toBe($omani->id)
        ->and($resolver->ruleFor($employee, Carbon::create(2026, 12, 31))?->id)->toBe($omani->id)
        ->and($resolver->ruleFor($employee, Carbon::create(2025, 12, 31)))->toBeNull()
        ->and($resolver->ruleFor($employee, Carbon::create(2027, 1, 1)))->toBeNull();
});

it('PasiRulesTest: picks the rule of the employee category', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');
    $expat = PasiFixtures::rule(PasiCategory::Expat, 0, 2, null, '2026-01-01');

    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'IN');

    expect(app(PasiRuleResolver::class)->ruleFor($employee, Carbon::create(2026, 6, 30))?->id)->toBe($expat->id);
});

it('PasiRulesTest: returns null for an employee of unknown category', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');

    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), null);

    expect(app(PasiRuleResolver::class)->ruleFor($employee, Carbon::create(2026, 6, 30)))->toBeNull();
});

it('PasiRulesTest: uses the new rate for the month a change takes effect mid-month', function (): void {
    $old = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');
    $new = app(PasiRuleManager::class)->addRule(PasiCategory::Omani, 8, 12, null, Carbon::create(2027, 3, 15));

    $employee = PasiFixtures::employee(PasiFixtures::company('OM'), 'OM');
    $resolver = app(PasiRuleResolver::class);

    expect($resolver->ruleFor($employee, Carbon::create(2027, 3, 31))?->id)->toBe($new->id)
        ->and($resolver->ruleFor($employee, Carbon::create(2027, 2, 28))?->id)->toBe($old->id);
});

it('PasiRulesTest: adding a rate closes the open rule the day before', function (): void {
    $old = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');

    $new = app(PasiRuleManager::class)->addRule(PasiCategory::Omani, 8, 12, 3000, Carbon::create(2027, 1, 1));

    expect($old->fresh()->effective_to->toDateString())->toBe('2026-12-31')
        ->and($new->effective_to)->toBeNull()
        ->and($new->effective_from->toDateString())->toBe('2027-01-01')
        ->and((float) $new->salary_cap)->toBe(3000.0)
        ->and(PasiRule::query()->where('category', 'omani')->count())->toBe(2);
});

it('PasiRulesTest: refuses a new rate that starts on or before the open rule', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01');

    expect(fn () => app(PasiRuleManager::class)->addRule(PasiCategory::Omani, 8, 12, null, Carbon::create(2026, 1, 1)))
        ->toThrow(ValidationException::class);

    expect(PasiRule::query()->count())->toBe(1);
});

it('PasiRulesTest: refuses overlapping ranges within a category but not across categories', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01', '2026-12-31');

    expect(fn () => PasiFixtures::rule(PasiCategory::Omani, 8, 12, null, '2026-06-01', '2027-06-30'))
        ->toThrow(ValidationException::class);

    expect(fn () => PasiFixtures::rule(PasiCategory::Omani, 8, 12, null, '2025-01-01'))
        ->toThrow(ValidationException::class);

    PasiFixtures::rule(PasiCategory::Expat, 0, 2, null, '2026-06-01', '2027-06-30');
    $adjacent = PasiFixtures::rule(PasiCategory::Omani, 8, 12, null, '2027-01-01');

    expect($adjacent->exists)->toBeTrue();
});

it('PasiRulesTest: refuses to edit a rule into an overlap', function (): void {
    PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01', '2026-12-31');
    $later = PasiFixtures::rule(PasiCategory::Omani, 8, 12, null, '2027-01-01');

    expect(fn () => $later->update(['effective_from' => '2026-12-31']))
        ->toThrow(ValidationException::class);

    $later->refresh()->update(['employee_rate' => 8.5]);

    expect((float) $later->fresh()->employee_rate)->toBe(8.5);
});

it('PasiRulesTest: refuses a zero or negative salary cap because it would remove all PASI', function (float $cap): void {
    try {
        PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, $cap, '2026-01-01');
        $this->fail('A non-positive salary cap was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('salary_cap');
    }

    expect(PasiRule::query()->count())->toBe(0);
})->with([0.0, -5.0]);

it('PasiRulesTest: accepts an empty or positive salary cap', function (): void {
    $uncapped = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, null, '2026-01-01', '2026-12-31');
    $capped = PasiFixtures::rule(PasiCategory::Omani, 7, 11.5, 3000, '2027-01-01');

    expect($uncapped->salary_cap)->toBeNull()
        ->and((float) $capped->salary_cap)->toBe(3000.0);
});
