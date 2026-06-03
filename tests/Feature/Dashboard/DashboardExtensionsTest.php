<?php

use App\Filament\Meetings\Pages\ExtendedMeetingDashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Projects\Pages\ExtendedProjectDashboard;
use App\Support\Dashboard\DashboardMetricCache;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Webkul\Security\Models\User;

it('caches pending approval metrics', function (): void {
    Cache::flush();

    $first = DashboardMetricCache::remember('pending_approvals', fn (): int => 42);
    $second = DashboardMetricCache::remember('pending_approvals', fn (): int => 99);

    expect($first)->toBe(42)
        ->and($second)->toBe(42);
});

it('clears metric cache on forget', function (): void {
    DashboardMetricCache::remember('pending_approvals', fn (): int => 7);
    DashboardMetricCache::forget('pending_approvals');

    $value = DashboardMetricCache::remember('pending_approvals', fn (): int => 3);

    expect($value)->toBe(3);
});

it('renders extended meeting dashboard when meetings plugin is available', function (): void {
    if (! class_exists(ExtendedMeetingDashboard::class)) {
        $this->markTestSkipped('Meetings plugin not installed.');
    }

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $this->actingAs($user);

    if (! ExtendedMeetingDashboard::canAccess()) {
        $this->markTestSkipped('Current user cannot access meetings dashboard.');
    }

    Livewire::test(ExtendedMeetingDashboard::class)
        ->assertSuccessful()
        ->assertDontSee(__('dashboard.hub.title'));

    expect(app(ExtendedMeetingDashboard::class)->getDashboardHubLinks())->toBe([]);
});

it('extended project dashboard does not render module hub cards', function (): void {
    if (! class_exists(ExtendedProjectDashboard::class)) {
        $this->markTestSkipped('Project dashboard not available.');
    }

    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $this->actingAs($user);

    if (! ExtendedProjectDashboard::canAccess()) {
        $this->markTestSkipped('Current user cannot access project dashboard.');
    }

    Livewire::test(ExtendedProjectDashboard::class)
        ->assertSuccessful()
        ->assertDontSee(__('dashboard.hub.title'));

    expect(app(ExtendedProjectDashboard::class)->getDashboardHubLinks())->toBe([]);
});

it('org dashboard refresh clears metric cache keys', function (): void {
    $user = User::withoutEvents(fn (): User => User::factory()->create(['is_active' => true]));
    $this->actingAs($user);

    DashboardMetricCache::remember('pending_approvals', fn (): int => 1);

    Livewire::test(Dashboard::class)
        ->callAction('refresh')
        ->assertSuccessful();

    $value = DashboardMetricCache::remember('pending_approvals', fn (): int => 2);

    expect($value)->toBe(2);
});
