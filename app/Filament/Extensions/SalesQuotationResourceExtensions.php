<?php

namespace App\Filament\Extensions;

use App\Filament\Extensions\Concerns\OptimizesRepeaterSelects;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Field\Filament\Forms\Components\ProgressStepper as FormProgressStepper;

class SalesQuotationResourceExtensions
{
    use OptimizesRepeaterSelects;

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

        return static::optimizeFormSelects($schema);
    }

    public static function localizeTable(Table $table): Table
    {
        static::walkTableColumns($table->getColumns(), function (Column $column): void {
            if (! $column instanceof TextColumn) {
                return;
            }

            $summarizerLabel = match ($column->getName()) {
                'amount_untaxed' => __('sales-extensions::table.summarize.total'),
                'amount_tax'     => __('sales-extensions::table.summarize.taxes'),
                'amount_total'   => __('sales-extensions::table.summarize.total-amount'),
                default          => null,
            };

            if ($summarizerLabel === null) {
                return;
            }

            $column->summarize(Sum::make()->label($summarizerLabel));
        });

        return $table;
    }

    /**
     * @param  array<Column>  $columns
     */
    protected static function walkTableColumns(array $columns, Closure $callback): void
    {
        foreach ($columns as $column) {
            $callback($column);

            if (method_exists($column, 'getColumns')) {
                static::walkTableColumns($column->getColumns(), $callback);
            }
        }
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
