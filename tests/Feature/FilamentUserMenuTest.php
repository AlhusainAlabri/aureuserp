<?php

use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Filament\View\PanelsRenderHook;

it('does not show the support plugin version in the profile menu hook', function (): void {
    app(ViewManager::class);

    expect(FilamentView::hasRenderHook(PanelsRenderHook::USER_MENU_PROFILE_BEFORE))->toBeFalse();
});
