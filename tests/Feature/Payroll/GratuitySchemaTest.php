<?php

use App\Services\Hr\HrExtensionSchemaService;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Employee\Models\DepartureReason;
use Webkul\Payroll\Enums\SettlementStatus;
use Webkul\Payroll\Models\GratuityTier;
use Webkul\Payroll\Services\GratuityDefaults;
use Webkul\Payroll\Settings\GratuitySettings;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components']);
    app(HrExtensionSchemaService::class)->ensure();
});

it('GratuitySchemaTest: seeds the default tiers once', function (): void {
    GratuityTier::query()->delete();

    GratuityDefaults::seedTiers();
    GratuityDefaults::seedTiers();

    $tiers = GratuityTier::query()->orderBy('from_year')->get();

    expect($tiers)->toHaveCount(2)
        ->and((float) $tiers[0]->from_year)->toBe(0.0)
        ->and((float) $tiers[0]->to_year)->toBe(3.0)
        ->and((float) $tiers[0]->days_per_year)->toBe(15.0)
        ->and((float) $tiers[1]->from_year)->toBe(3.0)
        ->and($tiers[1]->to_year)->toBeNull()
        ->and((float) $tiers[1]->days_per_year)->toBe(30.0)
        ->and($tiers[0]->effective_from->toDateString())->toBe('2000-01-01')
        ->and($tiers[1]->effective_to)->toBeNull();
});

it('GratuitySchemaTest: resolves the default settings', function (): void {
    $settings = app(GratuitySettings::class);

    expect((float) $settings->minimum_service_years)->toBe(1.0)
        ->and($settings->eligible_category)->toBe('expat')
        ->and($settings->leave_payout_divisor)->toBe(30);
});

it('GratuitySchemaTest: the settlements table has every column of the spec', function (): void {
    $columns = Schema::getColumnListing('payroll_end_of_service_settlements');

    expect($columns)->toContain(
        'id', 'employee_id', 'company_id', 'join_date', 'departure_date', 'service_days', 'last_basic',
        'gratuity_breakdown', 'gratuity_amount', 'unused_leave_days', 'leave_payout_amount',
        'loan_offset_amount', 'final_salary_amount', 'other_adjustment', 'adjustment_note', 'net_amount',
        'status', 'approved_by', 'approved_at', 'paid_at', 'creator_id', 'created_at', 'updated_at', 'deleted_at',
    );
});

it('GratuitySchemaTest: departure reasons pay gratuity by default and the flag is assignable', function (): void {
    $default = DepartureReason::query()->create(['name' => 'Default '.uniqid()]);
    $off = DepartureReason::query()->create(['name' => 'Off '.uniqid(), 'pays_gratuity' => false]);

    expect($default->fresh()->pays_gratuity)->toBeTrue()
        ->and($off->fresh()->pays_gratuity)->toBeFalse();
});

it('GratuitySchemaTest: settlement statuses are labelled in both locales', function (): void {
    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);

        foreach (SettlementStatus::cases() as $case) {
            expect($case->getLabel())->not->toContain('payroll::enums');
        }
    }
});
