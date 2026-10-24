@php
    /** @var array<int, array<string, mixed>> $docs */
    $isRtl = app()->getLocale() === 'ar';
    $align = $isRtl ? 'right' : 'left';
    $opposite = $isRtl ? 'left' : 'right';
    $order = fn (array $cells): array => $isRtl ? array_reverse($cells) : $cells;
    $addressLines = function ($model): array {
        if (! $model) {
            return [];
        }

        $street = collect([$model->street1, $model->street2])->filter()->implode('، ');
        $city = collect([$model->city, $model->state?->name, $model->zip])->filter()->implode('، ');

        return array_values(array_filter([$street, $city, $model->country?->name]));
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $docs[0]['title'] ?? '' }}</title>
    <style>
        @page { margin: 1.4cm 1.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; direction: {{ $isRtl ? 'rtl' : 'ltr' }}; text-align: {{ $align }}; }
        table { width: 100%; border-collapse: collapse; }
        td, th { text-align: {{ $align }}; vertical-align: top; }
        .muted { color: #6b7280; }
        .company-name { font-size: 14pt; color: #1a4587; font-weight: bold; margin-bottom: 3px; }
        .title { font-size: 18pt; color: #1a4587; font-weight: bold; margin-bottom: 12px; }
        .rule { border-top: 2px solid #1a4587; margin: 12px 0 14px; }
        .label { color: #6b7280; font-size: 8.5pt; }
        .party-title { color: #1a4587; font-weight: bold; margin-bottom: 4px; }
        .meta td { padding: 3px 0; }
        .lines { margin-top: 16px; }
        .lines th { background: #1a4587; color: #ffffff; padding: 7px 8px; font-size: 9pt; }
        .lines td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
        .num { text-align: {{ $opposite }}; }
        .totals { margin-top: 14px; }
        .totals td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        .totals .grand td { background: #f3f4f6; font-weight: bold; font-size: 11pt; border-top: 2px solid #1a4587; }
        .totals .due td { font-weight: bold; color: #b91c1c; }
        .note { margin-top: 18px; padding: 10px 12px; background: #f3f4f6; border-{{ $align }}: 3px solid #1a4587; }
        .closing { margin-top: 22px; text-align: center; color: #6b7280; }
    </style>
</head>
<body>
@foreach ($docs as $doc)
    @php
        $company = $doc['company'];
        $party = $doc['party'];
        $columns = $order($doc['columns']);
    @endphp

    <div @if (! $loop->last) style="page-break-after: always;" @endif>
        <div class="company-name">{{ $company?->name }}</div>
        @foreach ($addressLines($company?->partner) as $addressLine)
            <div class="muted">{{ $addressLine }}</div>
        @endforeach
        @if ($company?->email || $company?->phone)
            <div class="muted">{{ collect([$company?->email ? __('Email:').' '.$company->email : null, $company?->phone ? __('Phone:').' '.$company->phone : null])->filter()->implode('   |   ') }}</div>
        @endif
        @if ($company?->tax_id)
            <div class="muted">{{ __('invoices-extensions::pdf.tax-id') }}: {{ $company->tax_id }}</div>
        @endif

        <div class="rule"></div>

        <div class="title">{{ $doc['title'] }}</div>

        <table>
            <tr>
                @foreach ($order($party ? [1, 2] : [2]) as $cell)
                    @if ($cell === 1)
                        <td style="width: 55%;">
                            <div class="party-title">{{ $doc['partyLabel'] }}</div>
                            <div><strong>{{ $party?->name }}</strong></div>
                            @foreach ($addressLines($party) as $addressLine)
                                <div>{{ $addressLine }}</div>
                            @endforeach
                            @if ($party?->email)
                                <div>{{ __('Email:') }} {{ $party->email }}</div>
                            @endif
                            @if ($party?->phone)
                                <div>{{ __('Phone:') }} {{ $party->phone }}</div>
                            @endif
                            @if ($party?->tax_id)
                                <div>{{ __('invoices-extensions::pdf.tax-id') }}: {{ $party->tax_id }}</div>
                            @endif
                        </td>
                    @else
                        <td style="width: {{ $party ? '45%' : '100%' }};">
                            <table class="meta">
                                @foreach ($doc['meta'] as [$metaLabel, $metaValue])
                                    <tr>
                                        @foreach ($order(['label', 'value']) as $part)
                                            @if ($part === 'label')
                                                <td class="label">{{ $metaLabel }}</td>
                                            @else
                                                <td>{{ $metaValue }}</td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    @endif
                @endforeach
            </tr>
        </table>

        @if (! empty($doc['columns']))
        <table class="lines">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th @isset($column['width']) style="width: {{ $column['width'] }};" @endisset @if ($column['numeric'] ?? false) class="num" @endif>{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($doc['rows'] as $row)
                    <tr>
                        @foreach ($columns as $column)
                            <td @if ($column['numeric'] ?? false) class="num" @endif>{{ $row[$column['key']] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @foreach ($doc['tables'] ?? [] as $extra)
            @php($extraColumns = $order($extra['columns']))
            <div class="party-title" style="margin-top: 16px;">{{ $extra['title'] }}</div>
            <table class="lines" style="margin-top: 4px;">
                <thead>
                    <tr>
                        @foreach ($extraColumns as $column)
                            <th @isset($column['width']) style="width: {{ $column['width'] }};" @endisset @if ($column['numeric'] ?? false) class="num" @endif>{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($extra['rows'] as $row)
                        <tr>
                            @foreach ($extraColumns as $column)
                                <td @if ($column['numeric'] ?? false) class="num" @endif>{{ $row[$column['key']] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

        @if (! empty($doc['totals']))
        <table style="margin-top: 6px;">
            <tr>
                @foreach ($order([1, 2]) as $cell)
                    @if ($cell === 1)
                        <td style="width: 55%;"></td>
                    @else
                        <td style="width: 45%;">
                            <table class="totals">
                                @foreach ($doc['totals'] as [$rowLabel, $rowValue, $rowClass])
                                    <tr class="{{ $rowClass }}">
                                        @foreach ($order(['label', 'value']) as $part)
                                            @if ($part === 'label')
                                                <td>{{ $rowLabel }}</td>
                                            @else
                                                <td class="num">{{ $rowValue }}</td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    @endif
                @endforeach
            </tr>
        </table>
        @endif

        @foreach ($doc['notes'] as $note)
            <div class="note">
                <div class="party-title">{{ $note['title'] }}</div>
                <div>{{ $note['text'] }}</div>
            </div>
        @endforeach

        @if (! empty($doc['closing']))
            <div class="closing">{{ $doc['closing'] }}</div>
        @endif
    </div>
@endforeach
</body>
</html>
