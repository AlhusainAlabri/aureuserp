<?php

it('loads full filament-tables arabic translations without partial overrides', function (): void {
    app()->setLocale('ar');

    $translator = app('translator');
    $translator->get('filament-tables::table.empty.heading', ['model' => 'اختبار']);

    $reflection = new ReflectionClass($translator);
    $property = $reflection->getProperty('loaded');
    $property->setAccessible(true);
    $loaded = $property->getValue($translator);

    expect($loaded['filament-tables']['table']['ar'])
        ->toHaveKey('empty')
        ->and($loaded['filament-tables']['table']['ar']['empty']['heading'])->toBe('لا توجد :model')
        ->and(__('filament-tables::table.empty.heading', ['model' => 'اختبار']))->toBe('لا توجد اختبار')
        ->and(__('filament-tables::table.fields.search.label'))->toBe('بحث')
        ->and(__('filament-tables::table.grouping.fields.group.label'))->toBe('تجميع حسب');
});
