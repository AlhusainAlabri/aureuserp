<?php

namespace App\Filament\Extensions\Concerns;

use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

trait OptimizesRepeaterSelects
{
    /** @var array<int, string> */
    protected static array $repeaterSelectFieldsToOptimize = [
        'product_id',
        'taxes',
        'product_packaging_id',
        'packaging_id',
    ];

    /** @var array<int, string> */
    protected static array $headerSelectFieldsToOptimize = [
        'partner_id',
    ];

    public static function optimizeFormSelects(Schema $schema): Schema
    {
        static::walkComponentsForSelectOptimization(
            $schema->getComponents(withHidden: true),
            function (mixed $component, bool $insideRepeater): void {
                if (! $component instanceof Select) {
                    return;
                }

                $name = $component->getName();

                if ($insideRepeater && in_array($name, static::$repeaterSelectFieldsToOptimize, true)) {
                    $component->preload(false);

                    return;
                }

                if (! $insideRepeater && in_array($name, static::$headerSelectFieldsToOptimize, true)) {
                    $component->preload(false);
                }
            },
        );

        return $schema;
    }

    /**
     * @param  array<Component>  $components
     */
    protected static function walkComponentsForSelectOptimization(array $components, Closure $callback, bool $insideRepeater = false): void
    {
        foreach ($components as $component) {
            if ($component instanceof Repeater) {
                $callback($component, false);

                $childComponents = $component->getDefaultChildComponents();

                if ($childComponents instanceof Schema) {
                    $childComponents = $childComponents->getComponents(withHidden: true);
                }

                if (is_array($childComponents) && $childComponents !== []) {
                    static::walkComponentsForSelectOptimization(
                        $childComponents,
                        $callback,
                        insideRepeater: true,
                    );
                }

                continue;
            }

            $callback($component, $insideRepeater);

            if (! method_exists($component, 'getDefaultChildComponents')) {
                continue;
            }

            $children = $component->getDefaultChildComponents();

            if ($children instanceof Schema) {
                $children = $children->getComponents(withHidden: true);
            }

            if (! is_array($children) || $children === []) {
                continue;
            }

            static::walkComponentsForSelectOptimization($children, $callback, $insideRepeater);
        }
    }
}
