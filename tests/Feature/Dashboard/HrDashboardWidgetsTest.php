<?php

use App\Filament\Widgets\Dashboard\MyLeaveBalanceWidget;
use App\Filament\Widgets\Dashboard\OpenSubmissionsWidget;
use App\Filament\Widgets\Dashboard\PendingLeaveRequestsWidget;
use App\Filament\Widgets\Dashboard\RecruitmentPipelineWidget;
use App\Filament\Widgets\Dashboard\WarningsIssuedWidget;
use App\Support\SchemaMemo;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\Security\Models\User;

function hrDashboardUserWithRole(string $roleName): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

it('renders open submissions widget for hr managers', function (): void {
    hrDashboardUserWithRole('hr_manager');

    $component = Livewire::test(OpenSubmissionsWidget::class)->assertSuccessful();

    if (SchemaMemo::hasTable('employees_employee_submissions')) {
        $component->assertDontSee(__('dashboard.plugin_not_installed'));
    }
});

it('renders warnings issued widget for hr managers', function (): void {
    hrDashboardUserWithRole('hr_manager');

    $component = Livewire::test(WarningsIssuedWidget::class)->assertSuccessful();

    if (SchemaMemo::hasTable('employees_employee_warnings')) {
        $component->assertDontSee(__('dashboard.plugin_not_installed'));
    }
});

it('renders recruitment pipeline widget for hr managers', function (): void {
    hrDashboardUserWithRole('hr_manager');

    $component = Livewire::test(RecruitmentPipelineWidget::class)->assertSuccessful();

    if (SchemaMemo::hasTable('recruitments_applicants')) {
        $component->assertDontSee(__('dashboard.plugin_not_installed'));
    }
});

it('renders pending leave requests widget for hr managers', function (): void {
    hrDashboardUserWithRole('hr_manager');

    $component = Livewire::test(PendingLeaveRequestsWidget::class)->assertSuccessful();

    if (SchemaMemo::hasTable('time_off_leaves')) {
        $component->assertDontSee(__('dashboard.plugin_not_installed'));
    }
});

it('renders my leave balance widget for employees', function (): void {
    hrDashboardUserWithRole('employee');

    $component = Livewire::test(MyLeaveBalanceWidget::class)->assertSuccessful();

    if (SchemaMemo::hasTable('time_off_leaves') && SchemaMemo::hasTable('time_off_leave_allocations')) {
        $component->assertDontSee(__('dashboard.plugin_not_installed'));
    }
});

it('includes arabic dashboard translations for hr widgets', function (): void {
    App::setLocale('ar');

    expect(__('dashboard.widgets.my_payslip', locale: 'ar'))->toBe('آخر كشف راتب')
        ->and(__('dashboard.widgets.active_loans', locale: 'ar'))->toBe('القروض النشطة')
        ->and(__('dashboard.stats.pending_batches', locale: 'ar'))->toBe('دفعات معلقة')
        ->and(__('dashboard.stats.active_loans', locale: 'ar'))->toBe('القروض النشطة');
});

it('disables polling on hr dashboard widgets', function (): void {
    $widgets = [
        OpenSubmissionsWidget::class,
        WarningsIssuedWidget::class,
        RecruitmentPipelineWidget::class,
        PendingLeaveRequestsWidget::class,
        MyLeaveBalanceWidget::class,
    ];

    foreach ($widgets as $widgetClass) {
        $reflection = new ReflectionClass($widgetClass);
        $defaults = $reflection->getDefaultProperties();

        expect($defaults)->toHaveKey('pollingInterval')
            ->and($defaults['pollingInterval'])->toBeNull();
    }
});

it('uses correct table names for hr widget guards', function (): void {
    expect(OpenSubmissionsWidget::class)->toBeString();

    $openSubmissionsSource = file_get_contents(app_path('Filament/Widgets/Dashboard/OpenSubmissionsWidget.php'));
    $warningsSource = file_get_contents(app_path('Filament/Widgets/Dashboard/WarningsIssuedWidget.php'));
    $recruitmentSource = file_get_contents(app_path('Filament/Widgets/Dashboard/RecruitmentPipelineWidget.php'));
    $leaveSource = file_get_contents(app_path('Filament/Widgets/Dashboard/PendingLeaveRequestsWidget.php'));

    expect($openSubmissionsSource)->toContain('employees_employee_submissions')
        ->and($openSubmissionsSource)->not->toContain("'employee_submissions'")
        ->and($warningsSource)->toContain('employees_employee_warnings')
        ->and($warningsSource)->not->toContain("'employee_warnings'")
        ->and($recruitmentSource)->toContain('recruitments_applicants')
        ->and($recruitmentSource)->not->toContain("'job_applications'")
        ->and($leaveSource)->toContain('time_off_leaves');
});
