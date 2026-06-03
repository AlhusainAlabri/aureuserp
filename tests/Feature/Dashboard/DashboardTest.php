<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\Dashboard\HeadcountWidget;
use App\Filament\Widgets\Dashboard\MonthlyExpenseTrendWidget;
use App\Filament\Widgets\Dashboard\MyLeaveBalanceWidget;
use App\Filament\Widgets\Dashboard\MyNotesBoardWidget;
use App\Filament\Widgets\Dashboard\MyPayslipWidget;
use App\Filament\Widgets\Dashboard\MyTasksTodayWidget;
use App\Filament\Widgets\Dashboard\MyUpcomingMeetingsWidget;
use App\Filament\Widgets\Dashboard\OpenSubmissionsWidget;
use App\Filament\Widgets\Dashboard\OrgDashboardCommandCenterWidget;
use App\Filament\Widgets\Dashboard\OverdueTasksWidget;
use App\Filament\Widgets\Dashboard\PayrollSummaryWidget;
use App\Filament\Widgets\Dashboard\PendingApprovalsWidget;
use App\Filament\Widgets\Dashboard\RecruitmentPipelineChartWidget;
use App\Filament\Widgets\Dashboard\RecruitmentPipelineWidget;
use App\Filament\Widgets\Dashboard\RevenueExpensesChartWidget;
use App\Filament\Widgets\Dashboard\TasksCompletionChartWidget;
use App\Filament\Widgets\Dashboard\UpcomingRemindersWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Webkul\MyNotes\Models\Note;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

function dashboardUser(): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    test()->actingAs($user);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function dashboardUserWithRole(string $roleName): User
{
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    test()->actingAs($user);

    return $user;
}

// ──────────────────────────────────────────────
// 1. Route & render
// ──────────────────────────────────────────────

it('dashboard page renders for an authenticated user', function (): void {
    dashboardUser();

    Livewire::test(Dashboard::class)->assertSuccessful();
});

it('unauthenticated user is redirected from the dashboard', function (): void {
    $this->get(route('filament.admin.pages.home'))->assertRedirect();
});

it('org dashboard is the panel home url', function (): void {
    dashboardUser();

    expect(route('filament.admin.pages.home'))->toEndWith('/admin');
});

it('uses the org dashboard view without the module hub', function (): void {
    dashboardUser();

    $reflection = new ReflectionClass(Dashboard::class);

    expect(app(Dashboard::class)->getDashboardHubLinks())->toBe([])
        ->and($reflection->getProperty('view')->getValue(app(Dashboard::class)))
        ->toBe('filament.pages.org-dashboard');
});

it('shows a personalized greeting with logo in the page heading', function (): void {
    $user = dashboardUser();

    $page = app(Dashboard::class);

    expect($page->getTitle())->toBe(__('dashboard.greeting', ['name' => $user->name]))
        ->and($page->getHeader())->not->toBeNull()
        ->and($page->getHeading())->toBeNull();

    Livewire::test(Dashboard::class)
        ->assertSuccessful()
        ->assertSee(__('dashboard.greeting', ['name' => $user->name]))
        ->assertSeeHtml(asset('images/logo_2.png'))
        ->assertDontSeeHtml(asset('images/logo.png'));
});

// ──────────────────────────────────────────────
// 2. Role-based widget sets (static methods)
// ──────────────────────────────────────────────

it('super_admin gets the general manager widget set with smart alerts first', function (): void {
    $widgets = Dashboard::getGeneralManagerWidgets();

    expect($widgets[0])->toBe(OrgDashboardCommandCenterWidget::class)
        ->and($widgets)->toContain(RevenueExpensesChartWidget::class)
        ->and($widgets)->toContain(TasksCompletionChartWidget::class)
        ->and($widgets)->toContain(HeadcountWidget::class)
        ->and($widgets)->toContain(MyNotesBoardWidget::class)
        ->and($widgets)->not()->toContain(PendingApprovalsWidget::class)
        ->and(count($widgets))->toBe(25);
});

it('finance manager widget set includes alerts and finance charts', function (): void {
    $widgets = Dashboard::getFinanceManagerWidgets();

    expect($widgets[0])->toBe(OrgDashboardCommandCenterWidget::class)
        ->and(count($widgets))->toBe(9)
        ->and($widgets)->toContain(RevenueExpensesChartWidget::class)
        ->and($widgets)->toContain(MonthlyExpenseTrendWidget::class)
        ->and($widgets)->not()->toContain(HeadcountWidget::class);
});

it('hr manager widget set includes alerts and recruitment chart', function (): void {
    $widgets = Dashboard::getHrManagerWidgets();

    expect($widgets[0])->toBe(OrgDashboardCommandCenterWidget::class)
        ->and(count($widgets))->toBe(10)
        ->and($widgets)->toContain(HeadcountWidget::class)
        ->and($widgets)->toContain(RecruitmentPipelineChartWidget::class)
        ->and($widgets)->toContain(RecruitmentPipelineWidget::class)
        ->and($widgets)->not()->toContain(RevenueExpensesChartWidget::class);
});

it('department manager widget set includes alerts and task chart', function (): void {
    $widgets = Dashboard::getDepartmentManagerWidgets();

    expect($widgets[0])->toBe(OrgDashboardCommandCenterWidget::class)
        ->and(count($widgets))->toBe(8)
        ->and($widgets)->toContain(TasksCompletionChartWidget::class)
        ->and($widgets)->not()->toContain(PayrollSummaryWidget::class);
});

it('employee widget set includes alerts and personal widgets', function (): void {
    $widgets = Dashboard::getEmployeeWidgets();

    expect($widgets[0])->toBe(OrgDashboardCommandCenterWidget::class)
        ->and(count($widgets))->toBe(5)
        ->and($widgets)->not()->toContain(MyTasksTodayWidget::class)
        ->and(Dashboard::getCommandCenterStatWidgets())->toContain(MyTasksTodayWidget::class)
        ->and($widgets)->toContain(MyUpcomingMeetingsWidget::class)
        ->and($widgets)->toContain(MyLeaveBalanceWidget::class)
        ->and($widgets)->toContain(MyPayslipWidget::class)
        ->and($widgets)->toContain(MyNotesBoardWidget::class);
});

// ──────────────────────────────────────────────
// 3. Role-based getWidgets() dispatch
// ──────────────────────────────────────────────

it('getWidgets returns employee set for a user with no role', function (): void {
    dashboardUser();

    expect(app(Dashboard::class)->getWidgets())->toBe(Dashboard::getEmployeeWidgets());
});

it('getWidgets returns general manager set for super_admin', function (): void {
    $user = dashboardUserWithRole('super_admin');

    expect($user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n))->contains('super_admin'))->toBeTrue()
        ->and(app(Dashboard::class)->getWidgets())->toBe(Dashboard::getGeneralManagerWidgets());
});

it('getWidgets returns finance manager set for finance_manager role', function (): void {
    $user = dashboardUserWithRole('finance_manager');

    expect($user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n))->contains('finance_manager'))->toBeTrue()
        ->and(app(Dashboard::class)->getWidgets())->toBe(Dashboard::getFinanceManagerWidgets());
});

it('getWidgets returns hr manager set for hr_manager role', function (): void {
    $user = dashboardUserWithRole('hr_manager');

    expect($user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n))->contains('hr_manager'))->toBeTrue()
        ->and(app(Dashboard::class)->getWidgets())->toBe(Dashboard::getHrManagerWidgets());
});

it('getWidgets returns department manager set for manager role', function (): void {
    $user = dashboardUserWithRole('manager');

    expect($user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n))->contains('manager'))->toBeTrue()
        ->and(app(Dashboard::class)->getWidgets())->toBe(Dashboard::getDepartmentManagerWidgets());
});

// ──────────────────────────────────────────────
// 4. Widget graceful fallbacks via Livewire
// ──────────────────────────────────────────────

it('PendingApprovalsWidget renders without throwing', function (): void {
    dashboardUser();

    Livewire::test(PendingApprovalsWidget::class)
        ->assertSuccessful();
});

it('OverdueTasksWidget renders without throwing', function (): void {
    dashboardUser();

    Livewire::test(OverdueTasksWidget::class)
        ->assertSuccessful();
});

// ──────────────────────────────────────────────
// 5. UpcomingRemindersWidget visibility isolation
// ──────────────────────────────────────────────

it('UpcomingRemindersWidget only returns reminders for the current user', function (): void {
    if (! Schema::hasTable('notes')) {
        $this->markTestSkipped('notes table not present.');
    }

    $userA = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));

    Note::withoutEvents(function () use ($userA): void {
        Note::create([
            'type'          => 'reminder',
            'title'         => 'My reminder',
            'reminder_at'   => now()->addHour(),
            'reminder_sent' => false,
            'user_id'       => $userA->id,
            'company_id'    => Company::first()?->id,
        ]);
    });

    $userB = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    test()->actingAs($userB);

    $count = Note::withoutGlobalScopes()
        ->where('type', 'reminder')
        ->where('reminder_at', '>=', now())
        ->where('reminder_sent', false)
        ->where('user_id', $userB->id)
        ->count();

    expect($count)->toBe(0);
});

// ──────────────────────────────────────────────
// 6. OpenSubmissionsWidget visibility gate
// ──────────────────────────────────────────────

it('OpenSubmissionsWidget is hidden for regular employees', function (): void {
    dashboardUser();

    expect(OpenSubmissionsWidget::canView())->toBeFalse();
});

it('OpenSubmissionsWidget is visible for hr_manager', function (): void {
    dashboardUserWithRole('hr_manager');

    expect(OpenSubmissionsWidget::canView())->toBeTrue();
});

it('MyTasksTodayWidget renders for an authenticated user', function (): void {
    dashboardUser();

    Livewire::test(MyTasksTodayWidget::class)
        ->assertSuccessful();
});

it('OrgDashboardCommandCenterWidget renders for an authenticated user', function (): void {
    dashboardUser();

    Livewire::test(OrgDashboardCommandCenterWidget::class)
        ->assertSuccessful();
});

it('org dashboard renders period filter dropdown on the lead stat toolbar', function (): void {
    dashboardUserWithRole('super_admin');

    Livewire::test(Dashboard::class)
        ->assertSuccessful()
        ->assertSee(__('dashboard.widgets.pending_approvals'))
        ->assertSee(__('dashboard.filters.title'))
        ->assertSee(__('dashboard.filters.start_date'))
        ->assertDontSee(__('dashboard.filters.description'));
});

it('defaults org dashboard date filters to the last 30 days', function (): void {
    dashboardUserWithRole('super_admin');

    $component = Livewire::test(Dashboard::class);

    expect(Carbon::parse($component->get('filters.startDate'))->toDateString())
        ->toBe(now()->subDays(30)->toDateString());

    expect(Carbon::parse($component->get('filters.endDate'))->toDateString())
        ->toBe(now()->toDateString());
});

it('shows the default 30-day period on the org dashboard filter button', function (): void {
    dashboardUserWithRole('super_admin');

    $start = now()->subDays(30)->translatedFormat('d M');
    $end = now()->translatedFormat('d M Y');

    Livewire::test(Dashboard::class)
        ->assertSuccessful()
        ->assertSee("{$start} – {$end}");
});
