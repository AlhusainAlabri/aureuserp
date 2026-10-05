<?php

use App\Filament\TimeOff\Widgets\LocalizedOverviewCalendarWidget;
use App\Support\SchemaMemo;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\FeatureTestBootstrap;
use Webkul\Security\Models\User;
use Webkul\TimeOff\Filament\Widgets\OverviewCalendarWidget as BaseOverviewCalendarWidget;

function overviewCalendarKebabLivewireName(): string
{
    return (string) Str::of(BaseOverviewCalendarWidget::class)
        ->replace(['/', '\\'], '.')
        ->explode('.')
        ->map(fn (string $segment): string => Str::kebab($segment))
        ->implode('.');
}

beforeEach(function (): void {
    FeatureTestBootstrap::bootstrap([
        'employees' => 'employees_employees',
        'time-off'  => 'time_off_leaves',
    ]);

    SchemaMemo::resetForTesting();

    foreach (array_unique(array_filter([
        BaseOverviewCalendarWidget::class,
        app('livewire.finder')->normalizeName(BaseOverviewCalendarWidget::class),
        overviewCalendarKebabLivewireName(),
    ])) as $name) {
        Livewire::component($name, LocalizedOverviewCalendarWidget::class);
    }
});

it('registers the localized overview calendar under both fqcn and kebab livewire names', function (): void {
    expect(get_class(Livewire::new(BaseOverviewCalendarWidget::class)))
        ->toBe(LocalizedOverviewCalendarWidget::class)
        ->and(get_class(Livewire::new(overviewCalendarKebabLivewireName())))
        ->toBe(LocalizedOverviewCalendarWidget::class);
});

it('uses a placeholder duration field instead of live text entry in calendar create form', function (): void {
    $source = file_get_contents(app_path('Filament/TimeOff/Concerns/StabilizesLeaveCalendarForm.php'));

    expect($source)->toContain('Placeholder::make(\'duration_info\')')
        ->and($source)->not->toContain('TextEntry::make(\'duration_info\')')
        ->and($source)->not->toContain("\$set('duration_info'");
});

it('keeps the overview leave create modal mounted after selecting dates', function (): void {
    if (! SchemaMemo::hasTable('time_off_leaves')) {
        $this->markTestSkipped('Time off tables are not installed in the test database.');
    }

    Permission::query()->firstOrCreate([
        'name'       => 'widget_time_off_overview_calendar_widget',
        'guard_name' => 'web',
    ]);

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $user->givePermissionTo('widget_time_off_overview_calendar_widget');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $component = Livewire::test(BaseOverviewCalendarWidget::class)
        ->assertSuccessful()
        ->call('mountAction', 'create')
        ->assertActionMounted('create');

    expect(get_class($component->instance()))->toBe(LocalizedOverviewCalendarWidget::class);

    $from = now()->addDays(3)->toDateString();
    $to = now()->addDays(5)->toDateString();

    $component
        ->set('mountedActions.0.data.request_date_from', $from)
        ->set('mountedActions.0.data.request_date_to', $to);

    expect($component->instance()->mountedActions)->not->toBeEmpty()
        ->and($component->instance()->mountedActions[0]['name'] ?? null)->toBe('create')
        ->and($component->instance()->mountedActions[0]['data']['request_date_from'] ?? null)->toBe($from)
        ->and($component->instance()->mountedActions[0]['data']['request_date_to'] ?? null)->toBe($to);
});
