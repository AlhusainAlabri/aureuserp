<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\OmrFormatter::isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('accounting-extensions::reporting.balance-sheet.title') }}</title>
    @include('accounting::filament.clusters.reporting.pages.pdfs.partials.styles')
</head>
<body>
    <div class="header">
        <h1>{{ __('accounting-extensions::reporting.balance-sheet.title') }}</h1>
        <p>{{ __('accounting-extensions::reporting.balance-sheet.heading', ['date' => \App\Support\OmrFormatter::localizedDate($data['date'])]) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">{{ __('accounting-extensions::reporting.account') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['sections'] as $section)
                <tr class="section-header"><td colspan="2">{{ $section['title'] }}</td></tr>
                @foreach($section['subsections'] as $subsection)
                    @php
                        $hasAccounts = count($subsection['accounts']) > 0;
                        $showSubsection = $hasAccounts || ! isset($subsection['show_if_empty']) || $subsection['show_if_empty'];
                    @endphp
                    @if($showSubsection)
                        <tr><td colspan="2" style="padding-left: 20px; font-weight: 600;">{{ $subsection['title'] }}</td></tr>
                        @if($hasAccounts)
                            @foreach($subsection['accounts'] as $account)
                                <tr>
                                    <td style="padding-left: 40px;">{{ $account['code'] ? $account['code'].' - ' : '' }}{{ $account['name'] }}</td>
                                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account['balance']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-line">
                                <td style="padding-left: 20px;">{{ $subsection['total_label'] }}</td>
                                <td class="text-right">{{ \App\Support\OmrFormatter::format($subsection['total']) }}</td>
                            </tr>
                        @endif
                    @endif
                @endforeach
                <tr class="total-line">
                    <td>{{ $section['total_label'] }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($section['total']) }}</td>
                </tr>
            @endforeach
            <tr class="total-line">
                <td>{{ $data['grand_total_label'] }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($data['grand_total']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">{{ __('accounting-extensions::reporting.generated-on', ['datetime' => now()->locale(app()->getLocale())->translatedFormat('j F Y H:i')]) }}</div>
</body>
</html>
