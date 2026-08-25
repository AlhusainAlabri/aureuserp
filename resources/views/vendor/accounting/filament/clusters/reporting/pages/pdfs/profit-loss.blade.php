<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\OmrFormatter::isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('accounting-extensions::reporting.profit-loss.title') }}</title>
    @include('accounting::filament.clusters.reporting.pages.pdfs.partials.styles')
</head>
<body>
    <div class="header">
        <h1>{{ __('accounting-extensions::reporting.profit-loss.title') }}</h1>
        <p>{{ __('accounting-extensions::reporting.profit-loss.heading', [
            'from' => \App\Support\OmrFormatter::localizedDate($data['date_from']),
            'to' => \App\Support\OmrFormatter::localizedDate($data['date_to']),
        ]) }}</p>
    </div>

    <table>
        @foreach($data['sections'] as $section)
            <tr class="section-header"><td colspan="2">{{ $section['title'] }}</td></tr>
            @if(! empty($section['accounts']))
                @foreach($section['accounts'] as $account)
                    <tr>
                        <td style="padding-left: 30px;">{{ $account['code'] ? $account['code'].' - ' : '' }}{{ $account['name'] }}</td>
                        <td class="text-right">{{ \App\Support\OmrFormatter::format($account['balance']) }}</td>
                    </tr>
                @endforeach
                <tr class="total-line">
                    <td>{{ $section['total_label'] }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($section['total']) }}</td>
                </tr>
            @else
                <tr><td colspan="2" style="padding-left: 30px; font-style: italic;">{{ $section['empty_message'] }}</td></tr>
            @endif
        @endforeach
        <tr class="total-line">
            <td>{{ $data['is_profit'] ? __('accounting-extensions::reporting.net-profit') : __('accounting-extensions::reporting.net-loss') }}</td>
            <td class="text-right">{{ \App\Support\OmrFormatter::format(abs($data['net_income'])) }}</td>
        </tr>
    </table>

    <div class="footer">{{ __('accounting-extensions::reporting.generated-on', ['datetime' => now()->locale(app()->getLocale())->translatedFormat('j F Y H:i')]) }}</div>
</body>
</html>
