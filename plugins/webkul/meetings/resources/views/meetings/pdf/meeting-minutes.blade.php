@php
    $isArabic = app()->getLocale() === 'ar';
    $direction = $isArabic ? 'rtl' : 'ltr';
    $textAlign = $isArabic ? 'right' : 'left';
    $fontFamily = $isArabic ? "'Cairo', 'Amiri', sans-serif" : "'DejaVu Sans', sans-serif";
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: {{ $fontFamily }}; direction: {{ $direction }}; text-align: {{ $textAlign }}; color: #111827; font-size: 12px; }
        h1 { text-align: center; font-size: 24px; margin: 8px 0 18px; }
        h2 { font-size: 15px; margin: 18px 0 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; }
        .header { display: table; width: 100%; margin-bottom: 16px; }
        .header > div { display: table-cell; width: 50%; vertical-align: top; }
        .center { text-align: center; }
        .muted { color: #6b7280; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    @include('meetings::meetings.partials.meeting-minutes-content')
</body>
</html>
