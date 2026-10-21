<?php

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DbSchema;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource;
use Webkul\Manufacturing\Models\Product;
use Webkul\Product\Enums\ProductType;
use Webkul\Support\Filament\Forms\Components\Repeater;

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'products'      => 'products_products',
        'manufacturing' => 'manufacturing_orders',
    ]);
});

/**
 * @return array<int, Select>
 */
function manufacturingOrderProductSelects(): array
{
    $productSelects = [];

    $walk = function (array $components) use (&$walk, &$productSelects): void {
        foreach ($components as $component) {
            if ($component instanceof Select && $component->getName() === 'product_id') {
                $productSelects[] = $component;
            }

            $children = null;

            if ($component instanceof Repeater || method_exists($component, 'getDefaultChildComponents')) {
                $children = $component->getDefaultChildComponents();
            }

            if ($children instanceof Schema) {
                $children = $children->getComponents(withHidden: true);
            }

            if (is_array($children) && $children !== []) {
                $walk($children);
            }
        }
    };

    $walk(ManufacturingOrderResource::form(Schema::make())->getComponents(withHidden: true));

    return $productSelects;
}

it('excludes soft-deleted products from manufacturing order product selection', function (): void {
    if (! DbSchema::hasTable('products_products')) {
        $this->markTestSkipped('products_products table is not available.');
    }

    $active = Product::factory()->create([
        'type'            => ProductType::GOODS,
        'is_configurable' => null,
        'name'            => 'Active Goods Product',
    ]);

    $deleted = Product::factory()->create([
        'type'            => ProductType::GOODS,
        'is_configurable' => null,
        'name'            => 'Deleted Goods Product',
    ]);
    $deleted->delete();

    $productSelects = manufacturingOrderProductSelects();

    expect($productSelects)->not->toBeEmpty();

    foreach ($productSelects as $select) {
        $modifier = (new ReflectionClass($select))
            ->getProperty('modifyRelationshipQueryUsing')
            ->getValue($select);

        expect($modifier)->toBeInstanceOf(Closure::class);

        $query = Product::query()->withTrashed();
        $modifier($query);

        expect($query->pluck('id')->all())
            ->toContain($active->id)
            ->not->toContain($deleted->id);
    }

    expect(Product::query()->withTrashed()->whereKey($deleted->id)->exists())->toBeTrue();
});
