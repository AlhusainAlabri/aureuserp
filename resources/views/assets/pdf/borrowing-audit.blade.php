<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('assets-extensions::audit.export_pdf') }}</title>
    <style>
        body { font-family: 'Cairo', 'Amiri', sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ __('assets-extensions::audit.export_pdf') }}</h1>
    <p><strong>{{ __('assets::assets.fields.name') }}:</strong> {{ $borrowing->asset?->name }}</p>
    <p><strong>{{ __('assets::assets.fields.asset_number') }}:</strong> {{ $borrowing->asset?->asset_number }}</p>
    <p><strong>{{ __('assets::assets.fields.employee') }}:</strong> {{ $borrowing->employee?->name }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('assets-extensions::audit.at') }}</th>
                <th>{{ __('assets-extensions::audit.event') }}</th>
                <th>{{ __('assets-extensions::audit.actor') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                <tr>
                    <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $event->event_type }}</td>
                    <td>{{ $event->actor?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
