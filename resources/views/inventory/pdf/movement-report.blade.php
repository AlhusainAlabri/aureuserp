<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('inventory-extensions::pdf.movement_report_title') }}</title>
    <style>
        @font-face {
            font-family: 'Cairo';
            src: local('Cairo');
        }

        body {
            font-family: 'Cairo', 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1F2937;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
        }

        .header {
            background: #EA580C;
            color: #fff;
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 6px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .meta {
            font-size: 10px;
            opacity: 0.95;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            border: 1px solid #E5E7EB;
            padding: 6px 8px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        th {
            background: #F3F4F6;
            font-weight: 700;
        }

        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #6B7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('inventory-extensions::pdf.movement_report_title') }}</h1>
        <div class="meta">
            {{ __('inventory-extensions::pdf.period') }}:
            {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('inventory-extensions::pdf.date') }}</th>
                <th>{{ __('inventory-extensions::pdf.reference') }}</th>
                <th>{{ __('inventory-extensions::pdf.product') }}</th>
                <th>{{ __('inventory-extensions::pdf.source') }}</th>
                <th>{{ __('inventory-extensions::pdf.destination') }}</th>
                <th>{{ __('inventory-extensions::pdf.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($moves as $move)
                <tr>
                    <td>{{ $move->updated_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ $move->reference ?? $move->operation?->name ?? '—' }}</td>
                    <td>{{ $move->product?->name ?? '—' }}</td>
                    <td>{{ $move->sourceLocation?->full_name ?? '—' }}</td>
                    <td>{{ $move->destinationLocation?->full_name ?? '—' }}</td>
                    <td>{{ number_format((float) $move->product_qty, 3) }} {{ $move->uom?->name ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">{{ __('inventory-extensions::pdf.no_movements') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('inventory-extensions::pdf.generated_at') }}: {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
