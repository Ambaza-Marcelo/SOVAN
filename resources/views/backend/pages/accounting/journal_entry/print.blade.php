<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">
        @page {
            margin: 14mm 8mm 14mm 8mm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #111;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        .header h2 {
            margin: 0 0 3px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .header h3 {
            margin: 0;
            font-size: 11px;
            font-weight: 600;
        }
        .meta-info {
            margin-bottom: 8px;
            font-size: 8.5px;
        }
        .meta-info p { margin: 1px 0; }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .ledger-table thead {
            display: table-header-group;
        }
        .ledger-table th,
        .ledger-table td {
            border: 1px solid #111;
            padding: 3px 4px;
            vertical-align: top;
        }
        .ledger-table thead th {
            background-color: #f3f3f3;
            text-align: center;
            font-weight: bold;
            font-size: 8.5px;
        }
        .ledger-table tbody tr {
            page-break-inside: avoid;
        }
        .ledger-table .account-header-row td {
            background-color: #fff;
            vertical-align: middle;
        }
        .ledger-table .account-label-cell {
            font-weight: 700;
            white-space: normal;
            word-break: break-word;
        }
        .ledger-table .account-tag {
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .ledger-table .account-name {
            font-size: 9px;
        }
        .ledger-table .account-total-row td {
            font-weight: 700;
            background-color: #f7f7f7;
        }
        .ledger-table .carry-forward-row td {
            font-weight: 700;
            background-color: #f7f7f7;
        }
        .ledger-table .label-cell {
            white-space: normal;
            word-break: break-word;
        }
        .ledger-table .amount-cell,
        .ledger-table .piece-cell,
        .ledger-table .date-cell {
            white-space: nowrap;
            word-break: keep-all;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            border: none;
            padding: 0;
            font-size: 8px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td>
                @if($setting)
                    <small>{{ $setting->commune }}-{{ $setting->zone }}, {{ $setting->rue }}</small><br>
                    @if(!empty($setting->nif))
                        <small>NIF : {{ $setting->nif }}@if(!empty($setting->rc))-RC : {{ $setting->rc }}@endif</small><br>
                    @endif
                    <small>{{ $setting->telephone1 }}-{{ $setting->telephone2 }}</small><br>
                    <small>{{ $setting->email }}</small>
                @endif
            </td>
        </tr>
    </table>

    <div class="header">
        <h2>@lang('messages.GRAND LIVRE DES COMPTES')</h2>
        <h3>{{ config('app.name', 'EGOTEK') }}</h3>
    </div>

    <div class="meta-info">
        <p>
            <strong>@lang('messages.Période :')</strong> {{ $periodStartFormatted }} au {{ $periodEndFormatted }}
            &nbsp;&nbsp; <strong>@lang('messages.Compte :')</strong> {{ $accountLabel }}
        </p>
        <p><strong>@lang('messages.Date de tirage :')</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 8%;">@lang('messages.DATE')</th>
                <th rowspan="2" style="width: 6%;">@lang('messages.N° PIÈCE')</th>
                <th rowspan="2" style="width: 12%;">@lang('messages.N° DOCUMENT')</th>
                <th rowspan="2" style="width: 34%;">@lang('messages.LIBELLÉ ÉCRITURE')</th>
                <th colspan="2" style="width: 20%;">@lang('messages.MOUVEMENTS')</th>
                <th colspan="2" style="width: 20%;">@lang('messages.SOLDES')</th>
            </tr>
            <tr>
                <th>@lang('messages.DÉBIT')</th>
                <th>@lang('messages.CRÉDIT')</th>
                <th>@lang('messages.DÉBIT')</th>
                <th>@lang('messages.CRÉDIT')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pdfRenderRows as $renderRow)
                @if ($renderRow['type'] === 'account_header')
                    <tr class="account-header-row">
                        <td class="date-cell">&nbsp;</td>
                        <td class="piece-cell">&nbsp;</td>
                        <td class="piece-cell">&nbsp;</td>
                        <td class="account-label-cell">
                            <span class="account-tag">COMPTE</span><br>
                            <span class="account-name">{{ $renderRow['account_number'] }} {{ $renderRow['account_name'] }}</span>
                        </td>
                        <td class="amount-cell">&nbsp;</td>
                        <td class="amount-cell">&nbsp;</td>
                        <td class="amount-cell">&nbsp;</td>
                        <td class="amount-cell">&nbsp;</td>
                    </tr>
                @elseif ($renderRow['type'] === 'entry')
                    @php $row = $renderRow['data']; @endphp
                    <tr class="entry-row">
                        <td class="text-center date-cell">{{ $row['operation_date'] ?? $row['date'] }}</td>
                        <td class="text-center piece-cell">{{ $row['cj'] }}</td>
                        <td class="text-center piece-cell">{{ $row['piece'] }}</td>
                        <td class="label-cell">{{ $row['label'] }}</td>
                        <td class="text-right amount-cell">{{ number_format($row['debit'], 0, ',', ' ') }}</td>
                        <td class="text-right amount-cell">{{ number_format($row['credit'], 0, ',', ' ') }}</td>
                        <td class="text-right amount-cell">{{ number_format($row['balance_debit'], 0, ',', ' ') }}</td>
                        <td class="text-right amount-cell">{{ number_format($row['balance_credit'], 0, ',', ' ') }}</td>
                    </tr>
                @elseif ($renderRow['type'] === 'account_total')
                    <tr class="account-total-row">
                        <td class="date-cell">&nbsp;</td>
                        <td class="piece-cell">&nbsp;</td>
                        <td class="piece-cell">&nbsp;</td>
                        <td class="text-right">Total compte {{ $renderRow['account_number'] }}</td>
                        <td class="text-right amount-cell">{{ number_format($renderRow['total_debit'], 0, ',', ' ') }}</td>
                        <td class="text-right amount-cell">{{ number_format($renderRow['total_credit'], 0, ',', ' ') }}</td>
                        <td class="text-right amount-cell">{{ number_format($renderRow['balance_debit'], 0, ',', ' ') }}</td>
                        <td class="text-right amount-cell">{{ number_format($renderRow['balance_credit'], 0, ',', ' ') }}</td>
                    </tr>
                @endif
            @endforeach
            <tr class="carry-forward-row">
                <td class="date-cell">&nbsp;</td>
                <td class="piece-cell">&nbsp;</td>
                <td class="piece-cell">&nbsp;</td>
                <td class="text-right">@lang('messages.A reporter :')</td>
                <td class="text-right amount-cell">{{ number_format($grandTotalDebit, 0, ',', ' ') }}</td>
                <td class="text-right amount-cell">{{ number_format($grandTotalCredit, 0, ',', ' ') }}</td>
                <td class="text-right amount-cell">{{ number_format($grandBalanceDebit, 0, ',', ' ') }}</td>
                <td class="text-right amount-cell">{{ number_format($grandBalanceCredit, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
            $pdf->page_text(500, 58, 'Page : {PAGE_NUM}', $font, 8, [0, 0, 0]);
        }
    </script>
</body>
</html>
