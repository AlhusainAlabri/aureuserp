<?php

it('gives every admin navigation group an icon so it can appear as a module in the launcher', function (): void {
    $source = (string) file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));

    preg_match_all('/NavigationGroup::make\(([^;]*?)\)(?=,\s*\n)/s', $source, $groups);

    expect($groups[1])->not->toBeEmpty();

    foreach ($groups[1] as $group) {
        expect($group)->toContain('->icon(');
    }
});
