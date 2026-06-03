<?php

namespace App\Filament\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;

class ExportDashboardPdfAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportDashboardPdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('dashboard.export_pdf'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function () {
                $user = auth()->user();
                $roles = $user?->roles?->pluck('name')->toArray() ?? [];
                $roleName = ! empty($roles) ? implode(', ', $roles) : 'Employee';

                $filters = $this->livewire->filters ?? [];
                $start = $filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
                $end = $filters['endDate'] ?? now()->format('Y-m-d');

                $data = [
                    'user'      => $user,
                    'role'      => $roleName,
                    'startDate' => $start,
                    'endDate'   => $end,
                    'generated' => now()->format('d M Y · h:i A'),
                ];

                $pdf = Pdf::loadView('dashboard.pdf.dashboard-report', $data)
                    ->setPaper('a4')
                    ->setOption('isRemoteEnabled', true)
                    ->setOption('defaultFont', 'Cairo');

                return response()->streamDownload(
                    fn () => print ($pdf->output()),
                    'dashboard-report-'.now()->format('Y-m-d').'.pdf'
                );
            });
    }
}
