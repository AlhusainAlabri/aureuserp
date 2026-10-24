<?php

use Filament\Facades\Filament;

it('has no English model labels left for admin resources in Arabic', function (): void {
    app()->setLocale('ar');

    $english = [];

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        foreach (['getModelLabel', 'getPluralModelLabel'] as $method) {
            $label = (string) $resource::$method();

            if (preg_match('/^[A-Za-z0-9 \-\/&.]+$/', $label)) {
                $english[$resource][$method] = $label;
            }
        }
    }

    expect($english)->toBe([]);
});

it('does not append an English plural "s" to Arabic resource labels', function (): void {
    app()->setLocale('ar');

    $stray = [];

    foreach (Filament::getPanels() as $panel) {
        foreach ($panel->getResources() as $resource) {
            foreach (['getModelLabel', 'getPluralModelLabel', 'getTitleCasePluralModelLabel'] as $method) {
                $label = (string) $resource::$method();

                if (preg_match('/\p{Arabic}s$/u', $label)) {
                    $stray[$resource][$method] = $label;
                }
            }
        }
    }

    expect($stray)->toBe([]);
});
