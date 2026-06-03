<?php

namespace App\Filament\Assets\Resources\AssetBorrowingResource\Pages;

use App\Filament\Assets\Actions\ApproveBorrowingAction;
use App\Filament\Assets\Actions\RejectBorrowingAction;
use App\Filament\Assets\Resources\AssetBorrowingResource;
use App\Filament\Traits\HasApprovalActions;
use App\Models\Assets\AssetBorrowingEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;

class ViewAssetBorrowing extends ViewRecord
{
    use HasApprovalActions;

    protected static string $resource = AssetBorrowingResource::class;

    public function getTitle(): string
    {
        return __('assets-extensions::pages.view_borrowing', [
            'asset' => $this->getRecord()->asset?->name ?? '—',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ApproveBorrowingAction::make(),
            RejectBorrowingAction::make(),
            ...$this->getApprovalActions(),
            Action::make('exportAuditPdf')
                ->label(__('assets-extensions::audit.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => Schema::hasTable('asset_borrowing_events'))
                ->action(function (): mixed {
                    $borrowing = $this->getRecord();
                    $events = AssetBorrowingEvent::query()
                        ->where('asset_borrowing_id', $borrowing->id)
                        ->orderBy('created_at')
                        ->get();

                    $html = view('assets.pdf.borrowing-audit', [
                        'borrowing' => $borrowing->load(['asset', 'employee']),
                        'events'    => $events,
                    ])->render();

                    if (class_exists(Pdf::class)) {
                        $pdf = Pdf::loadHTML($html);

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            'borrowing-audit-'.$borrowing->id.'.pdf',
                        );
                    }

                    return Response::streamDownload(
                        fn () => print ($html),
                        'borrowing-audit-'.$borrowing->id.'.html',
                    );
                }),
        ];
    }
}
