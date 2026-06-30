<?php

namespace App\Filament\Extensions;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Field\Filament\Forms\Components\ProgressStepper as FormProgressStepper;

class SalesQuotationResourceExtensions
{
    public static function localizeForm(Schema $schema): Schema
    {
        static::walkComponents(
            $schema->getComponents(withHidden: true),
            function (Component $component): void {
                if ($component instanceof FormProgressStepper && $component->getName() === 'state') {
                    $component->label(__('sales::models/order.log-attributes.state'));
                }
            },
        );

        return $schema;
    }

    public static function localizeTable(Table $table): Table
    {
        return $table;
    }

    /**
     * @param  array<Component>  $components
     */
    protected static function walkComponents(array $components, Closure $callback): void
    {
        foreach ($components as $component) {
            $callback($component);

            if (! method_exists($component, 'getChildComponents')) {
                continue;
            }

            $children = $component->getChildComponents();

            if ($children === []) {
                continue;
            }

            static::walkComponents($children, $callback);
        }
    }
}
