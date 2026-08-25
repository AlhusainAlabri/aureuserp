<?php

namespace App\Support\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Webkul\Account\Models\Move;

class InvoicePdf
{
    public static function defaultFont(): string
    {
        return app()->getLocale() === 'ar' ? 'Cairo' : 'DejaVu Sans';
    }

    public static function viewName(): string
    {
        return 'accounts::invoice/actions/preview.index';
    }

    public static function store(Move $record, string $contents): string
    {
        $fileName = 'invoice-'.$record->name.'-'.time().'.pdf';
        $filePath = 'invoices/pdf/'.now()->year.'/'.$fileName;

        Storage::disk('private')->put($filePath, $contents);

        return $filePath;
    }

    public static function generate(Move $record): string
    {
        return Pdf::loadView(static::viewName(), compact('record'))
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', static::defaultFont())
            ->output();
    }

    public static function formatAmount(Move $record): string
    {
        return static::formatMoney($record, (float) $record->amount_total);
    }

    public static function formatMoney(Move $record, float $amount): string
    {
        $decimals = static::decimalPlaces($record);
        $symbol = static::currencySymbol($record);

        return $symbol.' '.number_format($amount, $decimals);
    }

    public static function decimalPlaces(Move $record): int
    {
        return (int) ($record->currency?->decimal_places ?? 3);
    }

    public static function currencySymbol(Move $record): string
    {
        if ($record->currency?->symbol) {
            return $record->currency->symbol;
        }

        return app()->getLocale() === 'ar' ? 'ر.ع.' : 'OMR';
    }
}
