<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\OmrFormatter::isRtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('accounting-extensions::reporting.general-ledger.title') }}</title>
    @include('accounting::filament.clusters.reporting.pages.pdfs.partials.styles')
    <style>
        .account-header td { background: #f3f4f6; font-weight: bold; }
        .opening-balance td { font-style: italic; color: #6b7280; }
        .move-row { padding-{{ \App\Support\OmrFormatter::isRtl() ? 'right' : 'left' }}: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('accounting-extensions::reporting.general-ledger.title') }}</h1>
        <p>{{ __('accounting-extensions::reporting.general-ledger.heading', [
            'from' => \App\Support\OmrFormatter::localizedDate($data['date_from']),
            'to' => \App\Support\OmrFormatter::localizedDate($data['date_to']),
        ]) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">{{ __('accounting-extensions::reporting.account') }}</th>
                <th class="text-left">{{ __('accounting-extensions::reporting.date') }}</th>
                <th class="text-left">{{ __('accounting-extensions::reporting.communication') }}</th>
                <th class="text-left">{{ __('accounting-extensions::reporting.partner') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.debit') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.credit') }}</th>
                <th class="text-right">{{ __('accounting-extensions::reporting.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDebit = 0;
                $totalCredit = 0;
            @endphp

            @foreach($data['accounts'] as $account)
                @php
                    $totalDebit += $account->period_debit;
                    $totalCredit += $account->period_credit;
                    $isExpanded = in_array($account->id, $expandedAccounts ?? []);
                    $moves = $isExpanded ? $getAccountMoves($account->id) : [];
                    $runningBalance = $account->opening_balance;
                @endphp

                <tr class="account-header">
                    <td>{{ $account->code }} {{ $account->name }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->period_debit) }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->period_credit) }}</td>
                    <td class="text-right">{{ \App\Support\OmrFormatter::format($account->ending_balance) }}</td>
                </tr>

                @if($account->opening_balance != 0 && $isExpanded)
                    <tr class="opening-balance">
                        <td class="move-row">{{ __('accounting-extensions::reporting.opening-balance') }}</td>
                        <td>{{ \App\Support\OmrFormatter::localizedDate($data['date_from'], 'Y-m-d') }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right">{{ \App\Support\OmrFormatter::format($account->opening_balance) }}</td>
                    </tr>
                @endif

                @if($isExpanded)
                    @foreach($moves as $move)
                        @php
                            $runningBalance += $move['debit'] - $move['credit'];
                        @endphp
                        <tr>
                            <td class="move-row">{{ $move['move_name'] }}</td>
                            <td>{{ \App\Support\OmrFormatter::localizedDate($move['date'], 'Y-m-d') }}</td>
                            <td>{{ $move['name'] ?? '' }}</td>
                            <td>{{ $move['partner_name'] ?? '' }}</td>
                            <td class="text-right">{{ $move['debit'] > 0 ? \App\Support\OmrFormatter::format($move['debit']) : '' }}</td>
                            <td class="text-right">{{ $move['credit'] > 0 ? \App\Support\OmrFormatter::format($move['credit']) : '' }}</td>
                            <td class="text-right">{{ \App\Support\OmrFormatter::format($runningBalance) }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            <tr class="total-line">
                <td>{{ __('accounting-extensions::reporting.total') }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($totalDebit) }}</td>
                <td class="text-right">{{ \App\Support\OmrFormatter::format($totalCredit) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">{{ __('accounting-extensions::reporting.generated-on', ['datetime' => now()->locale(app()->getLocale())->translatedFormat('j F Y H:i')]) }}</div>
</body>
</html>
