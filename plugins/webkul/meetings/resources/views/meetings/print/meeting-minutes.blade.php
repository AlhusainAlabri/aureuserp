<div class="meeting-minutes-print" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">
    @include('meetings::meetings.partials.meeting-minutes-content')
</div>

<style>
    .meeting-minutes-print { color: #111827; font-size: 14px; }
    .meeting-minutes-print h1 { text-align: center; font-size: 22px; margin-bottom: 16px; }
    .meeting-minutes-print h2 { font-size: 16px; margin: 16px 0 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
    .meeting-minutes-print table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .meeting-minutes-print th, .meeting-minutes-print td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
    .meeting-minutes-print th { background: #f3f4f6; }
    .meeting-minutes-print .header { display: table; width: 100%; margin-bottom: 16px; }
    .meeting-minutes-print .header > div { display: table-cell; width: 50%; vertical-align: top; }
    .meeting-minutes-print .muted { color: #6b7280; }
    @media print {
        .fi-modal-window, .fi-modal-close-btn { display: none !important; }
    }
</style>
