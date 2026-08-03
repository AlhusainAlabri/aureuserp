<?php

use Illuminate\Support\Facades\Blade;
use Wezlo\FilamentApproval\FilamentApprovalServiceProvider;

it('registers filament approval anonymous components for view caching', function (): void {
    $aliases = Blade::getClassComponentAliases();

    expect($aliases)->toHaveKeys([
        'filament-approval::components.approval-status-badge',
        'filament-approval::components.approval-timeline',
    ]);
});

it('compiles filament approval history blade for view cache', function (): void {
    if (! class_exists(FilamentApprovalServiceProvider::class)) {
        $this->markTestSkipped('Filament approval package not installed.');
    }

    $path = base_path('vendor/wezlo/filament-approval/resources/views/infolists/approval-history.blade.php');
    $compiler = app('view')->getEngineResolver()->resolve('blade')->getCompiler();

    expect(fn () => $compiler->compile($path))->not->toThrow(InvalidArgumentException::class);
});
