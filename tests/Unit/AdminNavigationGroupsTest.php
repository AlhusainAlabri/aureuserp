<?php

it('does not put icons on admin navigation groups', function (): void {
    $source = (string) file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));

    expect($source)->not->toMatch('/NavigationGroup::make\([^;]*->icon\(/s');
});
