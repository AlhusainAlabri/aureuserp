<?php

it('lists every migration that skips itself when a plugin table is missing', function (): void {
    $known = array_merge(
        config('extension-schema.deferred_migrations'),
        config('extension-schema.data_only_migrations'),
    );

    $unlisted = collect(glob(database_path('migrations/*.php')))
        ->filter(function (string $path): bool {
            $source = file_get_contents($path);

            return (bool) preg_match('/if \(\s*(?:Schema::hasTable\([^)]*\)\s*\|\|\s*)*! Schema::hasTable\([^)]*\)[^{]*\{\s*return;/s', $source);
        })
        ->map(fn (string $path): string => basename($path, '.php'))
        ->reject(fn (string $name): bool => in_array($name, $known, true))
        ->values()
        ->all();

    expect($unlisted)->toBe([], 'Add these to config/extension-schema.php: '.implode(', ', $unlisted));
});

it('only references migration files that exist', function (): void {
    $names = array_merge(
        config('extension-schema.deferred_migrations'),
        config('extension-schema.data_only_migrations'),
    );

    foreach ($names as $name) {
        expect(database_path("migrations/{$name}.php"))->toBeFile();
    }
});

it('never re-runs data-only migrations', function (): void {
    expect(array_intersect(
        config('extension-schema.deferred_migrations'),
        config('extension-schema.data_only_migrations'),
    ))->toBe([]);
});
