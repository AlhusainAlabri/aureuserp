<?php

namespace App\Filament\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Webkul\Meetings\Models\Meeting;

class ExportMeetingPdfAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportMeetingPdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('meetings::meetings.actions.export_pdf'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (Meeting $record): bool => auth()->user()?->can('exportPdf', $record) ?? false)
            ->action(function (Meeting $record) {
                $record->loadMissing([
                    'company',
                    'project',
                    'chairPerson',
                    'secretary',
                    'attendees.user',
                    'tasks.assignee',
                    'attachments',
                    'approvals.actions.user',
                ]);

                $pdf = Pdf::loadView('meetings::meetings.pdf.meeting-minutes', ['meeting' => $record])
                    ->setPaper('a4')
                    ->setOption('isRemoteEnabled', true);

                $path = "meetings/pdf/{$record->meeting_number}.pdf";

                Storage::disk('private')->put($path, $pdf->output());

                $record->update(['pdf_path' => $path]);

                return response()->download(Storage::disk('private')->path($path));
            });
    }
}
