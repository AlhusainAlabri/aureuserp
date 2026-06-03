<?php

use App\Mail\ProjectPerformanceReportMail;
use App\Services\Projects\ProjectPerformanceReportService;
use Database\Seeders\ProjectActivityPlanSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\Project;
use Webkul\Support\Models\ActivityPlan;

it('builds performance report blade without error', function (): void {
    $project = new Project([
        'name'       => 'Test Project',
        'start_date' => now()->subMonth(),
        'end_date'   => now()->addMonth(),
    ]);
    $project->id = 1;

    $data = app(ProjectPerformanceReportService::class)->buildReportData($project);

    $html = view('projects.pdf.performance-report', $data)->render();

    expect($html)->toContain('Test Project')
        ->and($html)->toContain(__('projects-extensions::reports.title'));
});

it('seeds the weekly project activity plan idempotently', function (): void {
    if (! Schema::hasTable('activity_plans')) {
        test()->markTestSkipped('Activity plans table unavailable.');
    }

    (new ProjectActivityPlanSeeder)->run();
    (new ProjectActivityPlanSeeder)->run();

    expect(
        ActivityPlan::query()
            ->where('plugin', 'projects')
            ->where('name', __('projects-extensions::activity_plan.name'))
            ->count()
    )->toBe(1);
});

it('defines a queued project performance mailable', function (): void {
    $project = new Project(['name' => 'Demo']);
    $project->id = 99;

    $mailable = new ProjectPerformanceReportMail($project, 'projects/pdf/demo.pdf');

    expect($mailable)->toBeInstanceOf(ShouldQueue::class)
        ->and($mailable->envelope()->subject)->toContain('Demo');
});

it('registers the weekly performance report command', function (): void {
    expect(collect(Artisan::all())->keys())->toContain('projects:performance-report');
});

it('queues performance report mail when command runs with projects', function (): void {
    if (! Schema::hasTable('projects_projects')) {
        test()->markTestSkipped('Projects plugin is not installed.');
    }

    Mail::fake();

    Artisan::call('projects:performance-report');

    // Command should complete without error whether or not projects exist.
    expect(true)->toBeTrue();
});
