<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\OmrFormatter::isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('accounting-extensions::reporting.aged-payable.title') }}</title>
    @include('accounting::filament.clusters.reporting.pages.pdfs.partials.styles', ['landscape' => true])
</head>
<body>
    <div class="header">
        <h1>{{ __('accounting-extensions::reporting.aged-payable.title') }}</h1>
        <p>{{ __('accounting-extensions::reporting.aged-payable.heading', ['date' => \App\Support\OmrFormatter::localizedDate($asOfDate)]) }}</p>
    </div>

    @if(empty($partners))
        <p>{{ __('accounting-extensions::reporting.no-data') }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('accounting-extensions::reporting.partner') }}</th>
                    <th>{{ __('accounting-extensions::reporting.at-date') }}</th>
                    <th class="text-right">{{ __('accounting-extensions::reporting.total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($partners as $partner)
                    <tr>
                        <td>{{ $partner['partner_name'] }}</td>
                        <td class="text-right">{{ \App\Support\OmrFormatter::formatNonZero($partner['at_date']) }}</td>
                        <td class="text-right">{{ \App\Support\OmrFormatter::format($partner['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">{{ __('accounting-extensions::reporting.generated-on', ['datetime' => now()->locale(app()->getLocale())->translatedFormat('j F Y H:i')]) }}</div>
</body>
</html>
