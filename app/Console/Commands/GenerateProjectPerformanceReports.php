<?php

namespace App\Console\Commands;

use App\Mail\ProjectPerformanceReportMail;
use App\Services\Projects\ProjectPerformanceReportService;
use App\Services\Projects\ProjectStageHelper;
use Database\Seeders\ProjectActivityPlanSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\Project;

class GenerateProjectPerformanceReports extends Command
{
    protected $signature = 'projects:performance-report';

    protected $description = 'Generate and email weekly project performance reports to project managers';

    public function handle(ProjectPerformanceReportService $reportService): int
    {
        if (! Schema::hasTable('projects_projects')) {
            $this->warn('Projects plugin is not installed.');

            return self::SUCCESS;
        }

        (new ProjectActivityPlanSeeder)->run();

        $query = Project::query()->with('user');

        if (ProjectStageHelper::isAvailable()) {
            $query = ProjectStageHelper::applyStageFilter($query->where('is_active', true), 'in_progress');
        } else {
            $query->where('is_active', true);
        }

        $projects = $query->get();
        $sent = 0;

        foreach ($projects as $project) {
            $manager = $project->user;

            if (! $manager?->email) {
                continue;
            }

            $pdfPath = $reportService->storePdf($project);
            Mail::to($manager)->queue(new ProjectPerformanceReportMail($project, $pdfPath));
            $sent++;
        }

        $this->info("Queued {$sent} project performance report(s).");

        return self::SUCCESS;
    }
}
