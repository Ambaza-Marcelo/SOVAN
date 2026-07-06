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
        
        .profit-loss-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .profit-loss-table thead th {
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
        
        .profit-loss-table tbody td {
            font-size: 11px;
            padding: 8px 10px;
            border: 1px solid #e1e5eb;
        }
        
        .profit-loss-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }
        
        .profit-loss-table tbody tr.subtotal-row {
            background-color: #eef2f7;
            font-weight: 600;
            border-top: 2px solid #d9dde2;
        }
        
        .profit-loss-table tbody tr.subtotal-row td {
            font-weight: bold;
        }
        
        .profit-loss-table tbody tr.total-row {
            background-color: #d9dde2;
            font-weight: 700;
            font-size: 13px;
            border-top: 3px solid #2f3a4c;
        }
        
        .profit-loss-table tbody tr.total-row td {
            font-weight: bold;
        }
        
        .libelle-col {
            text-align: left;
            padding-left: 10px;
        }
        
        .montant-col {
            text-align: right;
            padding-right: 10px;
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
        'documentTitle' => __('messages.profit_and_loss_statement_title'),
        'documentNumber' => null,
        'date' => $reportGeneratedAt ?? null
    ])
    
    <div class="report-title">{{ __('messages.profit_and_loss_statement_title') }}</div>
    <div class="report-date">{{ __('messages.profit_and_loss_generated_at') }} {{ $reportGeneratedAt }}</div>
    
    <div class="balance-meta">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%;">
                <h5>{{ __('messages.profit_and_loss_statement_title') }}</h5>
                <p class="meta-line"><strong>{{ __('messages.profit_and_loss_structure') }} :</strong> {{ $companyName }}</p>
                <p class="meta-line"><strong>{{ __('messages.profit_and_loss_report_type') }} :</strong> {{ __('messages.profit_and_loss_report_type_value') }}</p>
            </div>
            <div style="display: table-cell; width: 50%; text-align: right;">
                <p class="meta-line"><strong>{{ __('messages.profit_and_loss_fiscal_year') }} :</strong> {{ $fiscalYear }}</p>
                <p class="meta-line"><strong>{{ __('messages.profit_and_loss_currency') }} :</strong> {{ config('app.currency', 'BIF') }}</p>
            </div>
        </div>
    </div>
    
    <table class="profit-loss-table">
        <thead>
            <tr>
                <th style="width: 70%;">{{ __('messages.profit_and_loss_labels') }}</th>
                <th style="width: 30%;">{{ __('messages.profit_and_loss_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statementData as $row)
                <tr class="{{ $row['type'] === 'subtotal' ? 'subtotal-row' : ($row['type'] === 'total' ? 'total-row' : '') }}">
                    <td class="libelle-col">{{ $row['libelle'] }}</td>
                    <td class="montant-col">{{ number_format($row['montant'], 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center">{{ __('messages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
