<?php

use App\Filament\Extensions\Concerns\OptimizesRepeaterSelects;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OptimizesRepeaterSelectsHarness
{
    use OptimizesRepeaterSelects;
}

it('disables preload on repeater catalog selects', function (): void {
    $schema = Schema::make()
        ->components([
            Select::make('partner_id')->preload(),
            Repeater::make('products')
                ->schema([
                    Select::make('product_id')->preload(),
                    Select::make('taxes')->preload()->multiple(),
                    TextInput::make('quantity'),
                ]),
        ]);

    OptimizesRepeaterSelectsHarness::optimizeFormSelects($schema);

    $components = $schema->getComponents(withHidden: true);
    $partnerSelect = $components[0];
    $repeater = $components[1];
    $repeaterChildren = $repeater->getDefaultChildComponents();
    $productSelect = $repeaterChildren[0];
    $taxSelect = $repeaterChildren[1];

    expect($partnerSelect)->toBeInstanceOf(Select::class)
        ->and($partnerSelect->isPreloaded())->toBeFalse()
        ->and($productSelect->isPreloaded())->toBeFalse()
        ->and($taxSelect->isPreloaded())->toBeFalse();
});
