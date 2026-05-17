<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { direction: rtl; font-family: DejaVu Sans, sans-serif; font-size: 13px; line-height: 1.8; }
        .header, .footer { width: 100%; }
        .title { text-align: center; font-size: 22px; font-weight: bold; margin: 24px 0; }
        .reference { font-size: 16px; font-weight: bold; }
        .meta { margin-bottom: 16px; }
        .divider { border-top: 1px solid #999; margin: 16px 0; }
        .signature { border: 1px solid #999; height: 100px; margin-top: 32px; padding: 12px; }
        .footer { position: fixed; bottom: 0; font-size: 11px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <strong>{{ $correspondence->company?->name ?? config('app.name') }}</strong>
        <div class="title">
            {{ $correspondence->type === 'internal' ? __('correspondence::correspondence.pdf.internal_title') : __('correspondence::correspondence.pdf.official_title') }}
        </div>
        <div class="reference">{{ __('correspondence::correspondence.reference_number') }}: {{ $correspondence->reference_number }}</div>
    </div>

    <div class="meta">
        <div>{{ __('correspondence::correspondence.date') }}: {{ $correspondence->created_at?->format('Y-m-d') }}</div>
        <div>{{ __('correspondence::correspondence.priority') }}: {{ trans('correspondence::correspondence.priorities.'.$correspondence->priority) }}</div>
        <div>{{ __('correspondence::correspondence.from_department') }}: {{ $correspondence->fromDepartment?->name ?? $correspondence->sender_name }}</div>
        <div>{{ __('correspondence::correspondence.to_department') }}: {{ $correspondence->toDepartment?->name ?? $correspondence->toUser?->name ?? $correspondence->sender_entity }}</div>
    </div>

    <h2>{{ $correspondence->subject }}</h2>
    <div class="divider"></div>
    <div>{!! $correspondence->body !!}</div>

    @if ($correspondence->attachments->isNotEmpty())
        <h3>{{ __('correspondence::correspondence.attachments') }}</h3>
        <ul>
            @foreach ($correspondence->attachments as $attachment)
                <li>{{ $attachment->file_name }}</li>
            @endforeach
        </ul>
    @endif

    @if ($correspondence->isOutgoing() && $correspondence->status === 'sent')
        <div class="signature">{{ __('correspondence::correspondence.pdf.signature') }}</div>
    @endif

    <div class="footer">
        {{ __('correspondence::correspondence.reference_number') }}: {{ $correspondence->reference_number }}
        |
        {{ __('correspondence::correspondence.pdf.created_by', ['user' => $correspondence->creator?->name, 'date' => now()->format('Y-m-d')]) }}
    </div>
</body>
</html>
