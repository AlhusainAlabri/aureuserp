<?php

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DbSchema;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Accounting\Filament\Clusters\Customers\Resources\InvoiceResource as AccountingInvoiceResource;
use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource as InvoicePluginInvoiceResource;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'partners' => 'partners_partners',
        'accounts' => 'accounts_account_moves',
    ]);

    $this->actingAs(User::factory()->create([
        'is_active' => true,
    ]));
});

/**
 * @return array<int, Select>
 */
function invoiceCustomerSelects(string $resourceClass): array
{
    $customerSelects = [];

    $walk = function (array $components) use (&$walk, &$customerSelects): void {
        foreach ($components as $component) {
            if ($component instanceof Select && $component->getName() === 'partner_id') {
                $customerSelects[] = $component;
            }

            if (! method_exists($component, 'getDefaultChildComponents')) {
                continue;
            }

            $children = $component->getDefaultChildComponents();

            if ($children instanceof Schema) {
                $children = $children->getComponents(withHidden: true);
            }

            if (is_array($children) && $children !== []) {
                $walk($children);
            }
        }
    };

    $walk($resourceClass::form(Schema::make())->getComponents(withHidden: true));

    return $customerSelects;
}

/**
 * @return array<int, class-string>
 */
function invoiceResourceClassesUnderTest(): array
{
    $classes = [];

    if (class_exists(InvoicePluginInvoiceResource::class)) {
        $classes[] = InvoicePluginInvoiceResource::class;
    }

    if (class_exists(AccountingInvoiceResource::class)) {
        $classes[] = AccountingInvoiceResource::class;
    }

    return $classes;
}

it('excludes soft-deleted customers from the add invoice customer selection', function (): void {
    if (! DbSchema::hasTable('partners_partners')) {
        $this->markTestSkipped('partners_partners table is not available.');
    }

    $resourceClasses = invoiceResourceClassesUnderTest();

    expect($resourceClasses)->not->toBeEmpty();

    $active = Partner::factory()->create([
        'name' => 'Active Invoice Customer',
    ]);

    $deleted = Partner::factory()->create([
        'name' => 'Deleted Invoice Customer',
    ]);
    $deleted->delete();

    foreach ($resourceClasses as $resourceClass) {
        $customerSelects = invoiceCustomerSelects($resourceClass);

        expect($customerSelects)->not->toBeEmpty();

        foreach ($customerSelects as $select) {
            $modifier = (new ReflectionClass($select))
                ->getProperty('modifyRelationshipQueryUsing')
                ->getValue($select);

            expect($modifier)->toBeInstanceOf(Closure::class);

            $query = Partner::query();
            $modifier($query);

            expect($query->pluck('id')->all())
                ->toContain($active->id)
                ->not->toContain($deleted->id);
        }
    }

    expect(Partner::query()->withTrashed()->whereKey($deleted->id)->exists())->toBeTrue();
});
