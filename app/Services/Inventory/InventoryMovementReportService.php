<?php

namespace App\Services\Inventory;

use App\Models\Inventory\InventoryReportArchive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Models\Move;

class InventoryMovementReportService
{
    /**
     * @param  array{
     *     warehouse_id?: int|null,
     *     product_id?: int|null,
     *     location_id?: int|null,
     * }  $filters
     * @return Collection<int, Move>
     */
    public function getMoves(Carbon $from, Carbon $to, array $filters = []): Collection
    {
        return Move::query()
            ->with(['product', 'operation', 'sourceLocation', 'destinationLocation', 'uom'])
            ->where('state', MoveState::DONE)
            ->whereBetween('updated_at', [$from, $to])
            ->when(
                filled($filters['product_id'] ?? null),
                fn ($query) => $query->where('product_id', $filters['product_id']),
            )
            ->when(
                filled($filters['location_id'] ?? null),
                fn ($query) => $query->where(function ($query) use ($filters): void {
                    $query->where('source_location_id', $filters['location_id'])
                        ->orWhere('destination_location_id', $filters['location_id']);
                }),
            )
            ->when(
                filled($filters['warehouse_id'] ?? null),
                fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']),
            )
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function storePdf(Carbon $from, Carbon $to, array $filters = []): string
    {
        $moves = $this->getMoves($from, $to, $filters);
        $path = $this->buildPath($from, $to, 'pdf');

        $pdf = Pdf::loadView('inventory.pdf.movement-report', [
            'moves' => $moves,
            'from'  => $from,
            'to'    => $to,
        ])->setPaper('a4', 'landscape');

        Storage::disk('private')->put($path, $pdf->output());

        $this->archiveReport($from, $to, $path, 'pdf', $filters);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function storeCsv(Carbon $from, Carbon $to, array $filters = []): string
    {
        $moves = $this->getMoves($from, $to, $filters);
        $path = $this->buildPath($from, $to, 'csv');

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            __('inventory-extensions::pdf.date'),
            __('inventory-extensions::pdf.reference'),
            __('inventory-extensions::pdf.product'),
            __('inventory-extensions::pdf.source'),
            __('inventory-extensions::pdf.destination'),
            __('inventory-extensions::pdf.quantity'),
        ]);

        foreach ($moves as $move) {
            fputcsv($handle, [
                $move->updated_at?->format('Y-m-d H:i'),
                $move->reference ?? $move->operation?->name ?? '—',
                $move->product?->name ?? '—',
                $move->sourceLocation?->full_name ?? '—',
                $move->destinationLocation?->full_name ?? '—',
                (string) $move->product_qty,
            ]);
        }

        rewind($handle);
        Storage::disk('private')->put($path, stream_get_contents($handle) ?: '');
        fclose($handle);

        $this->archiveReport($from, $to, $path, 'csv', $filters);

        return $path;
    }

    public function downloadUrl(string $path, int $minutes = 60): string
    {
        $disk = Storage::disk('private');

        if ($this->driverSupportsTemporaryUrls($disk)) {
            try {
                return $disk->temporaryUrl($path, now()->addMinutes($minutes));
            } catch (\RuntimeException) {
                // Local "private" disk does not support temporary URLs.
            }
        }

        return URL::temporarySignedRoute(
            'inventory.reports.download',
            now()->addMinutes($minutes),
            ['path' => $path],
        );
    }

    /**
     * @param  Filesystem  $disk
     */
    protected function driverSupportsTemporaryUrls($disk): bool
    {
        return method_exists($disk, 'temporaryUrl')
            && in_array(config('filesystems.disks.private.driver'), ['s3', 'local'], true)
            && config('filesystems.disks.private.driver') === 's3';
    }

    protected function buildPath(Carbon $from, Carbon $to, string $format): string
    {
        $year = now()->year;

        return "inventory/{$format}/{$year}/movement-report-{$from->format('Y-m-d')}-{$to->format('Y-m-d')}.{$format}";
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function archiveReport(Carbon $from, Carbon $to, string $path, string $format, array $filters): void
    {
        if (! class_exists(InventoryReportArchive::class)) {
            return;
        }

        InventoryReportArchive::query()->create([
            'report_type'    => 'movement',
            'period_from'    => $from->toDateString(),
            'period_to'      => $to->toDateString(),
            'file_path'      => $path,
            'file_format'    => $format,
            'generated_by'   => Auth::id(),
            'filters'        => $filters,
        ]);
    }
}
