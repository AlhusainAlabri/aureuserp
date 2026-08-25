@php
    use App\Support\Invoices\InvoicePdf;

    $isArabic = app()->getLocale() === 'ar';
    $direction = $isArabic ? 'rtl' : 'ltr';
    $textAlign = $isArabic ? 'right' : 'left';
    $reverseAlign = $isArabic ? 'left' : 'right';
    $fontFamily = $isArabic ? "'Cairo', 'Amiri', sans-serif" : "'DejaVu Sans', sans-serif";
    $typeLabel = __('invoices-extensions::pdf.invoice');
    $decimals = InvoicePdf::decimalPlaces($record);
    $formatMoney = fn (float $amount): string => InvoicePdf::formatMoney($record, $amount);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $direction }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        body {
            font-family: {{ $fontFamily }};
            font-size: 14px;
            color: #333333;
            line-height: 1.6;
            margin: 0;
            direction: {{ $direction }};
            text-align: {{ $textAlign }};
        }

        .agreement {
            margin-bottom: 50px;
            page-break-after: always;
        }

        .agreement:last-child {
            page-break-after: auto;
        }

        .header {
            width: 100%;
            margin-bottom: 30px;
        }

        .company-info {
            width: 50%;
            float: {{ $textAlign === 'right' ? 'right' : 'left' }};
        }

        .vendor-info {
            width: 45%;
            float: {{ $reverseAlign === 'right' ? 'right' : 'left' }};
            text-align: {{ $reverseAlign }};
            border-{{ $reverseAlign === 'right' ? 'right' : 'left' }}: 2px solid #f0f0f0;
            padding-{{ $reverseAlign === 'right' ? 'right' : 'left' }}: 20px;
        }

        .clearfix {
            clear: both;
        }

        .agreement-title {
            font-size: 24px;
            color: #1a4587;
            margin: 25px 0;
            padding: 15px 0;
            border-bottom: 2px solid #1a4587;
        }

        .details-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 10px;
            vertical-align: top;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .items-table th {
            background: #1a4587;
            color: white;
            padding: 12px;
            text-align: {{ $textAlign }};
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .items-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .summary {
            width: 100%;
            display: inline-block;
        }

        .summary table {
            float: {{ $reverseAlign === 'right' ? 'right' : 'left' }};
            width: 280px;
            padding-top: 5px;
            padding-bottom: 5px;
            white-space: nowrap;
        }

        .summary table td {
            padding: 5px 10px;
        }

        .summary table td:nth-child(2) {
            text-align: center;
        }

        .summary table td:nth-child(3) {
            text-align: {{ $reverseAlign }};
        }

        .payment-info {
            clear: both;
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            color: #1a4587;
        }

        .payment-info-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="agreement">
        <div class="header">
            <div class="company-info">
                <div style="font-size: 28px; color: #1a4587; margin-bottom: 10px;">{{ $record->company->name }}</div>

                @if ($record->company->partner)
                    <div>
                        {{ $record->company->partner->street1 }}

                        @if ($record->company->partner->street2)
                            , {{ $record->company->partner->street2 }}
                        @endif
                    </div>

                    <div>
                        {{ $record->company->partner->city }},

                        @if ($record->company->partner->state)
                            {{ $record->company->partner->state->name }},
                        @endif

                        {{ $record->company->partner->zip }}
                    </div>

                    @if ($record->company->partner->country)
                        <div>{{ $record->company->partner->country->name }}</div>
                    @endif

                    @if ($record->company->email)
                        <div>{{ __('invoices-extensions::pdf.email') }}: {{ $record->company->email }}</div>
                    @endif

                    @if ($record->company->phone)
                        <div>{{ __('invoices-extensions::pdf.phone') }}: {{ $record->company->phone }}</div>
                    @endif
                @endif
            </div>

            <div class="vendor-info">
                <div>{{ $record->partner->name }}</div>

                <div>
                    {{ $record->partner->street1 }}

                    @if ($record->partner->street2)
                        , {{ $record->partner->street2 }}
                    @endif
                </div>

                <div>
                    {{ $record->partner->city }},

                    @if ($record->partner->state)
                        {{ $record->partner->state->name }},
                    @endif

                    {{ $record->partner->zip }}
                </div>

                @if ($record->partner->country)
                    <div>{{ $record->partner->country->name }}</div>
                @endif

                @if ($record->partner->email)
                    <div>{{ __('invoices-extensions::pdf.email') }}: {{ $record->partner->email }}</div>
                @endif

                @if ($record->partner->phone)
                    <div>{{ __('invoices-extensions::pdf.phone') }}: {{ $record->partner->phone }}</div>
                @endif
            </div>

            <div class="clearfix"></div>
        </div>

        <div class="agreement-title">
            {{ __('invoices-extensions::pdf.reference', ['type' => $typeLabel, 'reference' => $record->name]) }}
        </div>

        <table class="details-table">
            <tr>
                @if ($record->invoice_date)
                    <td width="33%">
                        <strong>{{ __('invoices-extensions::pdf.invoice-date') }}</strong><br>
                        {{ $record->invoice_date }}
                    </td>
                @endif

                @if ($record->ref)
                    <td width="33%">
                        <strong>{{ __('invoices-extensions::pdf.source') }}</strong><br>
                        {{ $record->ref }}
                    </td>
                @endif

                @if ($record->invoice_date_due)
                    <td width="33%">
                        <strong>{{ __('invoices-extensions::pdf.due-date') }}</strong><br>
                        {{ $record->invoice_date_due?->format('Y-m-d') }}
                    </td>
                @endif
            </tr>
        </table>

        @if (! $record->invoiceLines->isEmpty())
            <table class="items-table">
                <thead>
                    <tr>
                        <th>{{ __('invoices-extensions::pdf.product') }}</th>
                        <th>{{ __('invoices-extensions::pdf.quantity') }}</th>

                        @if (app(\Webkul\Product\Settings\ProductSettings::class)->enable_uom)
                            <th>{{ __('invoices-extensions::pdf.unit') }}</th>
                        @endif

                        <th>{{ __('invoices-extensions::pdf.unit-price') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($record->invoiceLines as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ number_format($item->quantity, $decimals) }}</td>

                            @if (app(\Webkul\Product\Settings\ProductSettings::class)->enable_uom)
                                <td>{{ $item->product->uom->name }}</td>
                            @endif

                            <td>{{ $formatMoney((float) $item->price_unit) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="summary">
            <table>
                <tbody>
                    <tr>
                        <td>{{ __('invoices-extensions::pdf.subtotal') }}</td>
                        <td>-</td>
                        <td>{{ $formatMoney((float) $record->amount_untaxed) }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('invoices-extensions::pdf.tax') }}</td>
                        <td>-</td>
                        <td>{{ $formatMoney((float) $record->amount_tax) }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('invoices-extensions::pdf.discount') }}</td>
                        <td>-</td>
                        <td>-{{ $formatMoney((float) ($record->total_discount ?? 0)) }}</td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #FFFFFF;">
                            <b>{{ __('invoices-extensions::pdf.grand-total') }}</b>
                        </td>
                        <td style="border-top: 1px solid #FFFFFF;">-</td>
                        <td style="border-top: 1px solid #FFFFFF;">
                            <b>{{ $formatMoney((float) $record->amount_total) }}</b>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if ($record->name)
            <div class="payment-info">
                <div class="payment-info-title">{{ __('invoices-extensions::pdf.payment-information') }}</div>
                <div>
                    {{ __('invoices-extensions::pdf.payment-communication', ['reference' => $record->name]) }}
                    @if ($record?->partnerBank?->bank?->name || $record?->partnerBank?->account_number)
                        <br>
                        <span>{{ __('invoices-extensions::pdf.account-details') }}</span>
                        {{ $record?->partnerBank?->bank?->name ?? __('invoices-extensions::pdf.not-available') }}
                        ({{ $record?->partnerBank?->account_number ?? __('invoices-extensions::pdf.not-available') }})
                    @endif
                </div>
            </div>
        @endif
    </div>
</body>
</html>
