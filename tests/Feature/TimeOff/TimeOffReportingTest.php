<?php

use App\Filament\TimeOff\Pages\ExtendedByTypeReport;
use App\Filament\TimeOff\Widgets\LeaveTypeReportWidget;
use App\Support\SchemaMemo;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Security\Models\User;
use Webkul\TimeOff\Enums\State;

it('uses actual leave state values in the reporting widget', function (): void {
    $source = file_get_contents(app_path('Filament/TimeOff/Widgets/LeaveTypeReportWidget.php'));

    expect($source)->toContain('State::CONFIRM->value')
        ->and($source)->toContain('State::VALIDATE_ONE->value')
        ->and($source)->toContain('State::VALIDATE_TWO->value')
        ->and($source)->toContain('State::REFUSE->value')
        ->and($source)->not->toContain('"draft"')
        ->and($source)->not->toContain('"validate"')
        ->and($source)->not->toContain('"cancel"');
});

it('does not hardcode duration strings in the time off helper', function (): void {
    $source = file_get_contents(base_path('plugins/webkul/time-off/src/Traits/TimeOffHelper.php'));

    expect($source)->not->toContain("'To Date'")
        ->and($source)->not->toContain("'Please select dates'")
        ->and($source)->not->toContain("'0.5 day'")
        ->and($source)->not->toContain('working day');
});

it('includes arabic translations for by-type report filters', function (): void {
    App::setLocale('ar');

    expect(__('time-off::filament/pages/by-type.filters.status.all', locale: 'ar'))->toBe('جميع الحالات')
        ->and(__('time-off::filament/pages/by-type.subheading', locale: 'ar'))->toBe('تفصيل طلبات الإجازة حسب حالة الموافقة')
        ->and(__('time-off::filament/widgets/calendar-widget.form.fields.request-date-to', locale: 'ar'))->toBe('تاريخ نهاية الطلب')
        ->and(State::CONFIRM->getLabel())->toBe('بانتظار الموافقة');
});

it('includes english translations for calendar duration strings', function (): void {
    App::setLocale('en');

    expect(__('time-off::filament/widgets/calendar-widget.form.fields.half-day-duration', ['days' => '0.5']))
        ->toBe('0.5 day')
        ->and(__('time-off::filament/clusters/management/resources/time-off.infolist.entries.yes'))
        ->toBe('Yes')
        ->and(__('time-off::filament/clusters/management/resources/time-off.infolist.entries.no'))
        ->toBe('No');
});

it('registers extended time off dashboard and report overrides', function (): void {
    $source = file_get_contents(app_path('Providers/DashboardExtensionsServiceProvider.php'));

    expect($source)->toContain('ExtendedByTypeReport::class')
        ->and($source)->toContain('LeaveTypeReportWidget::class');
});

it('renders the extended by-type report page for authenticated users', function (): void {
    if (! SchemaMemo::hasTable('time_off_leaves')) {
        $this->markTestSkipped('Time off tables are not installed in the test database.');
    }

    Permission::query()->firstOrCreate(['name' => 'page_time_off_by_type', 'guard_name' => 'web']);
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $user->givePermissionTo('page_time_off_by_type');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user);

    Livewire::test(ExtendedByTypeReport::class)
        ->assertSuccessful()
        ->assertSee(__('time-off::filament/pages/by-type.navigation.title'));
});

it('renders the leave type report widget for authenticated users', function (): void {
    if (! SchemaMemo::hasTable('time_off_leaves')) {
        $this->markTestSkipped('Time off tables are not installed in the test database.');
    }

    Permission::query()->firstOrCreate(['name' => 'widget_time_off_leave_type_widget', 'guard_name' => 'web']);
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $user->givePermissionTo('widget_time_off_leave_type_widget');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($user);

    Livewire::test(LeaveTypeReportWidget::class)
        ->assertSuccessful()
        ->assertSee(__('time-off::filament/widgets/leave-type-widget.heading.title'));
});
