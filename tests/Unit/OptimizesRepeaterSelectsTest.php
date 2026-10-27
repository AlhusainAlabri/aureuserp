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

it('keeps a capped preload on header partner selects and disables it on repeater catalog selects', function (): void {
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
        ->and($partnerSelect->isPreloaded())->toBeTrue()
        ->and($partnerSelect->getOptionsLimit())->toBe(50)
        ->and($productSelect->isPreloaded())->toBeFalse()
        ->and($taxSelect->isPreloaded())->toBeFalse();
});

it('shows unit prices as decimal text with the currency decimals', function (): void {
    $input = TextInput::make('price_unit')->inputMode('decimal')->rule('numeric');

    expect($input->getType())->toBe('text')
        ->and($input->isNumeric())->toBeFalse()
        ->and(money_input(215, 3))->toBe('215.000')
        ->and(money_input('2.35', 3))->toBe('2.350');
});
