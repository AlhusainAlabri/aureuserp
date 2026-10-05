<?php

use App\Filament\TimeOff\Pages\ExtendedOverview;
use App\Filament\TimeOff\Pages\ExtendedTimeOffDashboard;
use App\Filament\TimeOff\Widgets\LocalizedCalendarWidget;
use App\Filament\TimeOff\Widgets\LocalizedOverviewCalendarWidget;
use Livewire\Livewire;
use Webkul\FullCalendar\Filament\Actions\CreateAction;
use Webkul\TimeOff\Filament\Widgets\OverviewCalendarWidget as BaseOverviewCalendarWidget;

it('localizes leave calendar create modal heading in arabic', function (): void {
    app()->setLocale('ar');

    $widget = app(LocalizedCalendarWidget::class);
    $actions = invade($widget)->headerActions();

    $createAction = collect($actions)->first(
        fn ($action): bool => $action instanceof CreateAction
    );

    expect($createAction)->not->toBeNull()
        ->and($createAction->getModelLabel())->toBe('إجازة')
        ->and($createAction->getModalHeading())->toBe('إضافة إجازة');
});

it('does not duplicate the leave calendar create header button after boot', function (): void {
    $component = Livewire::test(LocalizedCalendarWidget::class)->assertSuccessful();

    $createActions = collect($component->instance()->getCachedHeaderActions())
        ->filter(fn ($action): bool => $action instanceof CreateAction);

    expect($createActions)->toHaveCount(1);
});

it('localizes overview leave calendar create modal heading in arabic', function (): void {
    app()->setLocale('ar');

    $widget = app(LocalizedOverviewCalendarWidget::class);
    $actions = invade($widget)->headerActions();

    $createAction = collect($actions)->first(
        fn ($action): bool => $action instanceof CreateAction
    );

    expect($createAction)->not->toBeNull()
        ->and($createAction->getModelLabel())->toBe('إجازة')
        ->and($createAction->getModalHeading())->toBe('إضافة إجازة');
});

it('uses localized leave calendar widgets on time-off pages', function (): void {
    expect(app(ExtendedTimeOffDashboard::class)->getWidgets())
        ->toContain(LocalizedCalendarWidget::class)
        ->and(app(ExtendedOverview::class)->getWidgets())
        ->toContain(BaseOverviewCalendarWidget::class)
        ->and(get_class(Livewire::new(BaseOverviewCalendarWidget::class)))
        ->toBe(LocalizedOverviewCalendarWidget::class);
});
