<?php

namespace App\Services\Projects;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Webkul\Project\Enums\TaskState;
use Webkul\Project\Models\Project;

class ProjectPerformanceReportService
{
    public function __construct(
        private readonly ProjectCompletionService $completionService,
        private readonly ProjectFinancialSummaryService $financialSummaryService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(Project $project): array
    {
        $financial = $this->financialSummaryService->summarize($project);
        $completion = $this->completionService->calculate($project);

        $tasksQuery = $project->tasks()->whereNull('parent_id');
        $totalTasks = (clone $tasksQuery)->where('state', '!=', TaskState::CANCELLED->value)->count();
        $doneTasks = (clone $tasksQuery)->where('state', TaskState::DONE->value)->count();
        $openTasks = (clone $tasksQuery)->whereNotIn('state', [TaskState::DONE->value, TaskState::CANCELLED->value])->count();

        $overdueTasks = (clone $tasksQuery)
            ->whereNotIn('state', [TaskState::DONE->value, TaskState::CANCELLED->value])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', now())
            ->count();

        $upcomingMilestones = $project->milestones()
            ->where('is_completed', false)
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', now())
            ->orderBy('deadline')
            ->limit(5)
            ->get(['name', 'deadline']);

        $locale = app()->getLocale();

        return [
            'project'            => $project,
            'completion'         => $completion,
            'completion_label'   => $this->completionService->formatPercentage($completion),
            'financial'          => $financial,
            'purchase_total'     => $this->financialSummaryService->formatOmr($financial['purchase_total']),
            'invoice_total'      => $this->financialSummaryService->formatOmr($financial['invoice_total']),
            'grand_total'        => $this->financialSummaryService->formatOmr($financial['grand_total']),
            'total_tasks'        => $totalTasks,
            'done_tasks'         => $doneTasks,
            'open_tasks'         => $openTasks,
            'overdue_tasks'      => $overdueTasks,
            'upcoming_milestones'=> $upcomingMilestones,
            'generated_at'       => now()->locale($locale)->translatedFormat('d M Y · h:i A'),
            'period'             => now()->startOfWeek()->locale($locale)->translatedFormat('d M Y')
                .' - '
                .now()->endOfWeek()->locale($locale)->translatedFormat('d M Y'),
            'is_rtl'             => $locale === 'ar',
            'logo_src'           => $this->resolveLogoSrc(),
        ];
    }

    protected function resolveLogoSrc(): ?string
    {
        foreach (['images/logo.png', 'images/logo.svg'] as $relativePath) {
            $path = public_path($relativePath);

            if (! is_file($path)) {
                continue;
            }

            $mime = str_ends_with($relativePath, '.svg') ? 'image/svg+xml' : 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        return null;
    }

    public function storePdf(Project $project): string
    {
        $data = $this->buildReportData($project);
        $filename = 'project-'.$project->id.'-'.now()->format('Ymd_His').'.pdf';
        $path = 'projects/pdf/'.$filename;

        $pdf = Pdf::loadView('projects.pdf.performance-report', $data)
            ->setPaper('a4');

        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }
}
