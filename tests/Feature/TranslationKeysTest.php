<?php

/**
 * Every literal translation key used in code must exist in Arabic and English,
 * otherwise users see the raw key (for example "employees::filament/…").
 *
 * @return array<string, list<string>> key => places it is used
 */
function missingTranslationKeys(string $locale): array
{
    $translator = app('translator');
    $missing = [];

    foreach (['app', 'plugins', 'resources/views'] as $root) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path($root), FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $path = $file->getPathname();

            if (! str_ends_with($path, '.php') || preg_match('#/(tests|database|node_modules|vendor)/|/resources/lang/#', $path)) {
                continue;
            }

            $source = file_get_contents($path);

            if (! preg_match_all('/(?<![\w>:$])(?:__|trans)\(\s*\'([a-z0-9_-]+::[a-z0-9_.\/-]+|[a-z0-9_-]+(?:\.[a-z0-9_-]+)+)\'/i', $source, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[1] as [$key, $offset]) {
                // Keys ending in "." or "_" are prefixes completed at runtime (…view_.$mode).
                if (preg_match('/[._]$|\.(php|css|js|json|png|svg)$/', $key) || $translator->has($key, $locale, false)) {
                    continue;
                }

                $line = substr_count(substr($source, 0, $offset), "\n") + 1;
                $missing[$key][] = str_replace(base_path().'/', '', $path).":{$line}";
            }
        }
    }

    ksort($missing);

    return $missing;
}

it('has an Arabic and English translation for every literal key used in code', function (string $locale): void {
    $missing = missingTranslationKeys($locale);

    // Shield's own "team" field uses a key that is both a string and a parent.
    unset($missing['filament-shield::filament-shield.field.team'], $missing['filament-shield::filament-shield.field.team.placeholder']);

    $report = collect($missing)->map(fn (array $places, string $key): string => "{$key} ({$places[0]})")->implode("\n");

    expect($missing)->toBe([], "Missing {$locale} translations:\n{$report}");
})->with(['ar', 'en']);

it('has an Arabic entry in ar.json for every English-sentence translation key used in code', function (): void {
    $arabic = json_decode((string) file_get_contents(lang_path('ar.json')), true, 512, JSON_THROW_ON_ERROR);
    $missing = [];

    foreach (['app', 'plugins', 'resources/views'] as $root) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path($root), FilesystemIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            $path = $file->getPathname();

            if (! str_ends_with($path, '.php') || preg_match('#/(tests|database|node_modules|vendor)/|/resources/lang/#', $path)) {
                continue;
            }

            if (! preg_match_all('/(?<![\w>:$])(?:__|trans)\(\s*([\'"])([^\'"\n]{2,400}?)\1\s*[,)]/', (string) file_get_contents($path), $matches)) {
                continue;
            }

            foreach ($matches[2] as $key) {
                $isStructuredKey = str_contains($key, '::') || preg_match('/^[a-z0-9_-]+(\.[a-z0-9_-]+)+$/', $key);

                if ($isStructuredKey || str_contains($key, '{$') || ! preg_match('/[A-Za-z]/', $key) || array_key_exists($key, $arabic)) {
                    continue;
                }

                $missing[$key] = str_replace(base_path().'/', '', $path);
            }
        }
    }

    ksort($missing);

    $report = collect($missing)->map(fn (string $path, string $key): string => "{$key} ({$path})")->implode("\n");

    expect($missing)->toBe([], "Add these to lang/ar.json:\n{$report}");
});
