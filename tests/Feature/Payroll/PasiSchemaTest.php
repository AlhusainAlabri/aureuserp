<?php

use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Payroll\Database\Seeders\DefaultSalaryComponentsSeeder;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\PasiRule;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Payroll\Services\PasiDefaults;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
});

it('PasiSchemaTest: seeds default omani and expat rules', function (): void {
    PasiRule::query()->delete();

    PasiDefaults::seedRules();
    PasiDefaults::seedRules();

    expect(PasiRule::query()->count())->toBe(2);

    $omani = PasiRule::query()->where('category', PasiCategory::Omani->value)->firstOrFail();
    expect((float) $omani->employee_rate)->toBe(7.0)
        ->and((float) $omani->employer_rate)->toBe(11.5)
        ->and($omani->salary_cap)->toBeNull()
        ->and($omani->effective_from->toDateString())->toBe('2000-01-01')
        ->and($omani->effective_to)->toBeNull();

    $expat = PasiRule::query()->where('category', PasiCategory::Expat->value)->firstOrFail();
    expect((float) $expat->employee_rate)->toBe(0.0)
        ->and((float) $expat->employer_rate)->toBe(0.0);
});

it('PasiSchemaTest: converts seeded PASI components', function (): void {
    SalaryComponent::query()->whereIn('code', ['BASIC', 'PASI_EMP', 'PASI_EMR', 'HOUSING'])->delete();

    $make = fn (string $code, SalaryComponentType $type, CalculationType $calc, ?float $percent) => SalaryComponent::query()->create([
        'code'              => $code,
        'name'              => $code,
        'name_ar'           => $code,
        'type'              => $type,
        'calculation_type'  => $calc,
        'default_percent'   => $percent,
        'is_pasi_insurable' => false,
        'is_active'         => true,
    ]);

    $make('BASIC', SalaryComponentType::Earning, CalculationType::Fixed, null);
    $make('PASI_EMP', SalaryComponentType::Deduction, CalculationType::PercentOfBasic, 7);
    $make('PASI_EMR', SalaryComponentType::EmployerCost, CalculationType::PercentOfBasic, 11.5);
    $make('HOUSING', SalaryComponentType::Earning, CalculationType::PercentOfBasic, 25);

    PasiDefaults::convertSeededComponents();
    PasiDefaults::convertSeededComponents();

    expect(SalaryComponent::query()->where('code', 'PASI_EMP')->value('calculation_type'))->toBe(CalculationType::PasiEmployee)
        ->and(SalaryComponent::query()->where('code', 'PASI_EMR')->value('calculation_type'))->toBe(CalculationType::PasiEmployer)
        ->and(SalaryComponent::query()->where('code', 'BASIC')->value('is_pasi_insurable'))->toBeTrue()
        ->and(SalaryComponent::query()->where('code', 'HOUSING')->value('calculation_type'))->toBe(CalculationType::PercentOfBasic)
        ->and(SalaryComponent::query()->where('code', 'HOUSING')->value('is_pasi_insurable'))->toBeFalse();
});

it('PasiSchemaTest: carries a customised component percent into the omani rule', function (): void {
    SalaryComponent::query()->whereIn('code', ['PASI_EMP', 'PASI_EMR'])->delete();
    PasiRule::query()->delete();
    PasiDefaults::seedRules();

    foreach ([['PASI_EMP', SalaryComponentType::Deduction, 8], ['PASI_EMR', SalaryComponentType::EmployerCost, 12]] as [$code, $type, $percent]) {
        SalaryComponent::query()->create([
            'code'             => $code,
            'name'             => $code,
            'name_ar'          => $code,
            'type'             => $type,
            'calculation_type' => CalculationType::PercentOfBasic,
            'default_percent'  => $percent,
            'is_active'        => true,
        ]);
    }

    PasiDefaults::convertSeededComponents();

    $omani = PasiRule::query()->where('category', PasiCategory::Omani->value)->firstOrFail();
    expect((float) $omani->employee_rate)->toBe(8.0)
        ->and((float) $omani->employer_rate)->toBe(12.0);
});

it('PasiSchemaTest: fresh seeder uses PASI types', function (): void {
    SalaryComponent::query()->whereIn('code', ['BASIC', 'HOUSING', 'TRANSPORT', 'PASI_EMP', 'PASI_EMR'])->delete();

    (new DefaultSalaryComponentsSeeder)->run();

    $employee = SalaryComponent::query()->where('code', 'PASI_EMP')->firstOrFail();
    $employer = SalaryComponent::query()->where('code', 'PASI_EMR')->firstOrFail();

    expect($employee->calculation_type)->toBe(CalculationType::PasiEmployee)
        ->and($employer->calculation_type)->toBe(CalculationType::PasiEmployer)
        ->and(SalaryComponent::query()->where('code', 'BASIC')->value('is_pasi_insurable'))->toBeTrue()
        ->and(PasiRule::query()->count())->toBeGreaterThanOrEqual(2);
});

it('PasiSchemaTest: has calculation type labels in both locales', function (): void {
    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);

        foreach ([CalculationType::PasiEmployee, CalculationType::PasiEmployer] as $case) {
            expect($case->getLabel())->not->toContain('payroll::enums');
        }

        foreach (PasiCategory::cases() as $case) {
            expect($case->getLabel())->not->toContain('payroll::enums');
        }
    }
});
