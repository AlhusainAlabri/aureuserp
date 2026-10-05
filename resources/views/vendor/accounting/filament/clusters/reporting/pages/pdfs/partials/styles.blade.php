@php
    $isRtl = \App\Support\OmrFormatter::isRtl();
@endphp
<style>
    @page { margin: 1.5cm; size: A4 {{ $landscape ?? false ? 'landscape' : 'portrait' }}; }
    body {
        font-family: {{ $isRtl ? "'DejaVu Sans', sans-serif" : "Arial, Helvetica, sans-serif" }};
        font-size: 11pt;
        color: #1f2937;
        line-height: 1.4;
        direction: {{ $isRtl ? 'rtl' : 'ltr' }};
    }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1f2937; padding-bottom: 10px; }
    .header h1 { margin: 0; font-size: 16pt; font-weight: bold; }
    .header p { margin: 5px 0 0; font-size: 10pt; color: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th { background: #f3f4f6; padding: 8px 10px; font-size: 10pt; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #d1d5db; }
    td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
    .text-right { text-align: {{ $isRtl ? 'left' : 'right' }}; }
    .text-left { text-align: {{ $isRtl ? 'right' : 'left' }}; }
    .section-header { background: #f9fafb; font-weight: bold; }
    .total-line { font-weight: bold; background: #f3f4f6; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9pt; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 5px; }
</style>
