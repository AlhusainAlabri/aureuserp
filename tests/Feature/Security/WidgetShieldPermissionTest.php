<?php

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Webkul\Security\Models\User;

it('can decide visibility for every app widget that extends a plugin widget', function (): void {
    $this->actingAs(User::factory()->create());

    $failures = [];

    foreach (glob(app_path('Filament/*/Widgets/*.php')) + glob(app_path('Filament/Widgets/*/*.php')) as $file) {
        $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen(app_path()) + 1));

        if (! class_exists($class) || ! in_array(HasWidgetShield::class, class_uses_recursive($class), true)) {
            continue;
        }

        try {
            $class::canView();
        } catch (Throwable $exception) {
            $failures[$class] = $exception->getMessage();
        }
    }

    expect($failures)->toBe([]);
});
