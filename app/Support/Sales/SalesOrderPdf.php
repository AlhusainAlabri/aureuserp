<?php

namespace App\Support\Sales;

use App\Support\Pdf\ArabicText;
use Illuminate\Support\Facades\Storage;
use Webkul\Sale\Models\Order;

class SalesOrderPdf
{
    public static function defaultFont(): string
    {
        return ArabicText::pdfFontFamily();
    }

    public static function store(Order $record, string $contents): string
    {
        $fileName = "{$record->name}-".time().'.pdf';
        $filePath = 'sales/'.now()->year.'/'.$fileName;

        Storage::disk('private')->put($filePath, $contents);

        return $filePath;
    }

    public static function formatAmount(Order $record): string
    {
        $decimals = (int) ($record->currency?->decimal_places ?? 3);
        $symbol = $record->currency?->symbol ?? 'ر.ع.';

        return $symbol.' '.number_format((float) $record->amount_total, $decimals);
    }

    public static function formatMoney(Order $record, float $amount): string
    {
        $decimals = (int) ($record->currency?->decimal_places ?? 3);
        $symbol = $record->currency?->symbol ?? 'ر.ع.';

        return $symbol.' '.number_format($amount, $decimals);
    }

    public static function decimalPlaces(Order $record): int
    {
        return (int) ($record->currency?->decimal_places ?? 3);
    }
}
