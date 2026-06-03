<!DOCTYPE html>
<html lang="{{ $is_rtl ? 'ar' : 'en' }}" dir="{{ $is_rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('projects-extensions::reports.title') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1F2937; direction: {{ $is_rtl ? 'rtl' : 'ltr' }}; }
        .header { background: #EA580C; color: #fff; padding: 16px; border-radius: 6px; margin-bottom: 16px; }
        .header h1 { margin: 0 0 6px; font-size: 20px; }
        .meta { font-size: 11px; opacity: 0.9; }
        .section { margin-bottom: 14px; border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px; }
        .section h2 { margin: 0 0 8px; font-size: 14px; color: #111827; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 6px 8px; border-bottom: 1px solid #F3F4F6; }
        .label { color: #6B7280; width: 45%; }
        .value { font-weight: bold; }
        .footer { margin-top: 20px; font-size: 10px; color: #9CA3AF; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $project->name }}</h1>
        <div class="meta">{{ __('projects-extensions::reports.title') }}</div>
        <div class="meta">{{ __('projects-extensions::reports.generated_at') }}: {{ $generated_at }}</div>
        <div class="meta">{{ __('projects-extensions::reports.period') }}: {{ $period }}</div>
    </div>

    <div class="section">
        <h2>{{ __('projects-extensions::reports.completion') }}</h2>
        <table class="grid">
            <tr>
                <td class="label">{{ __('projects-extensions::reports.completion') }}</td>
                <td class="value">{{ $completion_label }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>{{ __('projects-extensions::reports.financial_summary') }}</h2>
        <table class="grid">
            <tr>
                <td class="label">{{ __('projects-extensions::kpi.purchase_total') }}</td>
                <td class="value">{{ $purchase_total }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('projects-extensions::kpi.invoice_total') }}</td>
                <td class="value">{{ $invoice_total }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('projects-extensions::kpi.grand_total') }}</td>
                <td class="value">{{ $grand_total }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>{{ __('projects-extensions::reports.tasks_summary') }}</h2>
        <table class="grid">
            <tr>
                <td class="label">{{ __('projects-extensions::reports.total_tasks') }}</td>
                <td class="value">{{ $total_tasks }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('projects-extensions::reports.done_tasks') }}</td>
                <td class="value">{{ $done_tasks }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('projects-extensions::reports.open_tasks') }}</td>
                <td class="value">{{ $open_tasks }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('projects-extensions::reports.overdue_tasks') }}</td>
                <td class="value">{{ $overdue_tasks }}</td>
            </tr>
        </table>
    </div>

    @if ($upcoming_milestones->isNotEmpty())
        <div class="section">
            <h2>{{ __('projects-extensions::reports.upcoming_milestones') }}</h2>
            <table class="grid">
                @foreach ($upcoming_milestones as $milestone)
                    <tr>
                        <td class="label">{{ $milestone->name }}</td>
                        <td class="value">{{ $milestone->deadline?->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="footer">{{ $project->name }} · {{ $generated_at }}</div>
</body>
</html>
