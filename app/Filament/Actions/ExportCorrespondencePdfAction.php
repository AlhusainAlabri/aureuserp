<?php

namespace App\Filament\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Webkul\Correspondence\Models\Correspondence;

class ExportCorrespondencePdfAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportCorrespondencePdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('correspondence::correspondence.actions.export_pdf'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (Correspondence $record): bool => auth()->user()?->can('exportPdf', $record) ?? false)
            ->action(function (Correspondence $record) {
                $record->loadMissing([
                    'company',
                    'creator',
                    'fromDepartment',
                    'toDepartment',
                    'toUser',
                    'attachments',
                ]);

                $pdf = Pdf::loadView('correspondence::correspondence.pdf.letter', ['correspondence' => $record])
                    ->setPaper('a4')
                    ->setOption('isRemoteEnabled', true);

                $path = 'correspondence/pdf/'.str_replace('/', '-', $record->reference_number).'.pdf';

                Storage::disk('private')->put($path, $pdf->output());

                return response()->download(Storage::disk('private')->path($path));
            });
    }
}
