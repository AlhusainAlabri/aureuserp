<?php

namespace App\Filament\Actions;

use App\Services\Projects\ProjectPerformanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Webkul\Project\Models\Project;

class ExportProjectPerformancePdfAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportProjectPerformancePdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('projects-extensions::actions.export_performance_pdf'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function () {
                /** @var Project $project */
                $project = $this->getRecord();
                $data = app(ProjectPerformanceReportService::class)->buildReportData($project);

                $pdf = Pdf::loadView('projects.pdf.performance-report', $data)
                    ->setPaper('a4');

                return response()->streamDownload(
                    fn () => print ($pdf->output()),
                    'project-performance-'.$project->id.'.pdf',
                );
            });
    }
}
