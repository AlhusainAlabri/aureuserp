<?php

use App\Support\Livewire\KeepUnsavedChangesAlertAccurate;
use Livewire\ComponentHookRegistry;

it('is registered as a Livewire component hook', function (): void {
    $hooks = (new ReflectionClass(ComponentHookRegistry::class))->getProperty('componentHooks')->getValue();

    expect($hooks)->toContain(KeepUnsavedChangesAlertAccurate::class);
});
