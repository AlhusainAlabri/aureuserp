<?php

use App\Support\GlobalSearchProvider;
use Filament\Facades\Filament;
use Filament\Livewire\GlobalSearch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Webkul\Payroll\Enums\CalculationType;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\SalaryComponent;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

beforeEach(function (): void {
    if (! Schema::hasTable('payroll_salary_components')) {
        Artisan::call('payroll:install', ['--no-interaction' => true]);
    }

    $user = User::where('email', 'nodhumtech@gmail.com')->first()
        ?? User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('searches salary components on real database columns, not display_name', function (): void {
    expect(SalaryComponentResource::getGloballySearchableAttributes())
        ->toBe(['name', 'name_ar', 'code']);
});

it('does not 500 when global search runs against payroll salary components', function (): void {
    $company = Company::query()->first() ?? Company::factory()->create(['currency_id' => null]);

    SalaryComponent::query()->firstOrCreate(
        ['code' => 'GS13'],
        [
            'name'             => 'Global Search Thirteen',
            'name_ar'          => 'بحث عام ١٣',
            'type'             => SalaryComponentType::Earning,
            'calculation_type' => CalculationType::Fixed,
            'default_amount'   => 13,
            'is_taxable'       => true,
            'is_active'        => true,
            'sort_order'       => 99,
            'company_id'       => $company->id,
        ],
    );

    $provider = app(GlobalSearchProvider::class);

    expect(fn () => $provider->getResults('13'))->not->toThrow(Throwable::class);

    $results = $provider->getResults('13');

    expect($results)->not->toBeNull();

    Livewire::test(GlobalSearch::class)
        ->set('search', '13')
        ->assertSuccessful();
});
