<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\OmrFormatter::isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('accounting-extensions::reporting.trial-balance.title') }}</title>
    @include('accounting::filament.clusters.reporting.pages.pdfs.partials.styles')
</head>
<body>
    <div class="header">
        <h1>{{ __('accounting-extensions::reporting.trial-balance.title') }}</h1>
        <p>{{ __('accounting-extensions::reporting.trial-balance.heading', [
            'from' => \App\Support\OmrFormatter::localizedDate($data['date_from']),
            'to' => \App\Support\OmrFormatter::localizedDate($data['date_to']),
        ]) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting-extensions::reporting.account') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.debit') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.credit') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.debit') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.credit') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.debit') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.credit') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['accounts'] as $account)
                <tr>
                    <td>{{ $account->code ? $account->code.' ' : '' }}{{ $account->name }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->initial_debit) }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->initial_credit) }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->period_debit) }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->period_credit) }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->end_debit) }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->end_credit) }}</td>
                </tr>
            @endforeach
            <tr class="total-line">
                <td>{{ __('accounting-extensions::reporting.total') }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($data['totals']['initial_debit']) }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($data['totals']['initial_credit']) }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($data['totals']['period_debit']) }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($data['totals']['period_credit']) }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($data['totals']['end_debit']) }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($data['totals']['end_credit']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">{{ __('accounting-extensions::reporting.generated-on', ['datetime' => now()->locale(app()->getLocale())->translatedFormat('j F Y H:i')]) }}</div>
</body>
</html>
