<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">
        body {
            font-size: 12px;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .header-table {
            width: 100%;
            border: none;
            margin-bottom: 20px;
        }
        
        .header-table td {
            border: none;
            vertical-align: top;
        }
        
        .balance-meta {
            border: 1px solid #e1e5eb;
            border-radius: 6px;
            padding: 15px 20px;
            background-color: #f9fbfd;
            margin-bottom: 20px;
        }
        
        .balance-meta h5 {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .balance-meta .meta-line {
            font-size: 11px;
            margin-bottom: 5px;
            color: #4d5969;
        }
        
        .balance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .balance-table thead th {
            background-color: #eef2f7;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
            color: #2f3a4c;
            border: 1px solid #d9dde2;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        
        .balance-table tbody td {
            font-size: 11px;
            padding: 8px 10px;
            border: 1px solid #e1e5eb;
        }
        
        .balance-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }
        
        .balance-table tfoot td {
            background-color: #eef2f7;
            font-weight: 600;
            font-size: 11px;
            border-top: 2px solid #d9dde2;
            padding: 10px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .report-date {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    @include('backend.pages.document.partials._document_header', [
        'documentTitle' => __('messages.accounts_balance_title'),
        'documentNumber' => null,
        'documentDate' => $reportGeneratedAt ?? null,
    ])
    
    <div class="report-title">{{ __('messages.accounts_balance_title') }}</div>
    <div class="report-date">{{ __('messages.accounts_balance_generated_at') }} {{ $reportGeneratedAt }}</div>
    
    <div class="balance-meta">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%;">
                <h5>{{ __('messages.accounts_balance_title') }}</h5>
                <p class="meta-line"><strong>{{ __('messages.accounts_balance_structure') }} :</strong> {{ $companyName }}</p>
                <p class="meta-line"><strong>{{ __('messages.accounts_balance_report_type') }} :</strong> {{ __('messages.accounts_balance_report_type_value') }}</p>
            </div>
            <div style="display: table-cell; width: 50%; text-align: right;">
                <p class="meta-line"><strong>{{ __('messages.accounts_balance_fiscal_year') }} :</strong> {{ $fiscalYear }}</p>
                <p class="meta-line"><strong>{{ __('messages.accounts_balance_currency') }} :</strong> {{ config('app.currency', 'BIF') }}</p>
                @if (!empty($accountFilterLabel) && $accountFilterLabel !== 'Tous les comptes')
                    <p class="meta-line"><strong>Filtre comptes :</strong> {{ $accountFilterLabel }}</p>
                @endif
            </div>
        </div>
    </div>
    
    <table class="balance-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">{{ __('messages.accounts_balance_account_number') }}</th>
                <th rowspan="2" style="width: 30%;">{{ __('messages.accounts_balance_account_name') }}</th>
                <th colspan="2" class="text-center" style="width: 30%;">{{ __('messages.accounts_balance_movements') }}</th>
                <th colspan="2" class="text-center" style="width: 30%;">{{ __('messages.accounts_balance_balances') }}</th>
            </tr>
            <tr>
                <th>{{ __('messages.accounts_balance_debit') }}</th>
                <th>{{ __('messages.accounts_balance_credit') }}</th>
                <th>{{ __('messages.accounts_balance_debit') }}</th>
                <th>{{ __('messages.accounts_balance_credit') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($accountsData as $row)
                <tr>
                    <td>{{ $row['account_number'] }}</td>
                    <td>{{ $row['account_name'] ?: __('messages.accounts_balance_undefined_account') }}</td>
                    <td class="text-right">{{ number_format($row['movement_debit'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($row['movement_credit'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($row['balance_debit'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($row['balance_credit'], 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">{{ __('messages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-right"><strong>{{ __('messages.accounts_balance_carry_forward') }} :</strong></td>
                <td class="text-right"><strong>{{ number_format($grandTotals['movement_debit'], 0, ',', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($grandTotals['movement_credit'], 0, ',', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($grandTotals['balance_debit'], 0, ',', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($grandTotals['balance_credit'], 0, ',', ' ') }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
