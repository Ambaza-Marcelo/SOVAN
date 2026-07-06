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
        
        .passive-balance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .passive-balance-table thead th {
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
        
        .passive-balance-table tbody td {
            font-size: 11px;
            padding: 8px 10px;
            border: 1px solid #e1e5eb;
        }
        
        .passive-balance-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }
        
        .passive-balance-table tbody tr.title-row {
            background-color: #eef2f7;
            font-weight: 700;
            font-size: 12px;
        }
        
        .passive-balance-table tbody tr.title-row td {
            font-weight: bold;
        }
        
        .passive-balance-table tbody tr.subtotal-row {
            background-color: #eef2f7;
            font-weight: 600;
            border-top: 2px solid #d9dde2;
        }
        
        .passive-balance-table tbody tr.subtotal-row td {
            font-weight: bold;
        }
        
        .passive-balance-table tbody tr.total-row {
            background-color: #d9dde2;
            font-weight: 700;
            font-size: 13px;
            border-top: 3px solid #2f3a4c;
        }
        
        .passive-balance-table tbody tr.total-row td {
            font-weight: bold;
        }
        
        .passif-col {
            text-align: left;
        }
        
        .passif-col.level-0 {
            padding-left: 5px;
        }
        
        .passif-col.level-1 {
            padding-left: 20px;
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
        'documentTitle' => 'BILAN DE CAPITAUX PROPRES ET PASSIFS',
        'documentNumber' => null,
        'date' => $reportGeneratedAt ?? null
    ])
    
    <div class="report-title">@lang('messages.BILAN DE CAPITAUX PROPRES ET PASSIFS')</div>
    <div class="report-date">@lang('messages.Rapport généré le') {{ $reportGeneratedAt }}</div>
    
    <div class="balance-meta">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%;">
                <h5>@lang('messages.BILAN DE CAPITAUX PROPRES ET PASSIFS')</h5>
                <p class="meta-line"><strong>@lang('messages.Structure :')</strong> {{ $companyName }}</p>
                <p class="meta-line"><strong>@lang('messages.Type de rapport :')</strong> @lang('messages.Bilan de Capitaux Propres et Passifs')</p>
            </div>
            <div style="display: table-cell; width: 50%; text-align: right;">
                <p class="meta-line"><strong>@lang('messages.Année fiscale :')</strong> {{ $fiscalYear }}</p>
                <p class="meta-line"><strong>@lang('messages.Devise :')</strong> {{ config('app.currency', 'BIF') }}</p>
            </div>
        </div>
    </div>
    
    <table class="passive-balance-table">
        <thead>
            <tr>
                <th style="width: 70%;">@lang('messages.CAPITAUX PROPRES ET PASSIFS')</th>
                <th style="width: 30%;">@lang('messages.MONTANT')</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($balanceSheetData as $row)
                @if($row['type'] === 'title')
                    <tr class="title-row">
                        <td class="passif-col level-{{ $row['level'] ?? 0 }}" colspan="2"><strong>{{ $row['passif'] }}</strong></td>
                    </tr>
                @elseif($row['type'] === 'subtitle')
                    <tr>
                        <td class="passif-col level-{{ $row['level'] ?? 0 }}">{{ $row['passif'] }}</td>
                        <td class="montant-col">
                            @if(isset($row['montant']) && $row['montant'] !== '' && $row['montant'] !== null)
                                {{ number_format($row['montant'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </td>
                    </tr>
                @elseif($row['type'] === 'subtotal')
                    <tr class="subtotal-row">
                        <td class="passif-col level-{{ $row['level'] ?? 0 }}"><strong>{{ $row['passif'] }}</strong></td>
                        <td class="montant-col"><strong>
                            @if(isset($row['montant']) && $row['montant'] !== '' && $row['montant'] !== null)
                                {{ number_format($row['montant'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </strong></td>
                    </tr>
                @elseif($row['type'] === 'total')
                    <tr class="total-row">
                        <td class="passif-col level-{{ $row['level'] ?? 0 }}"><strong>{{ $row['passif'] }}</strong></td>
                        <td class="montant-col"><strong>
                            @if(isset($row['montant']) && $row['montant'] !== '' && $row['montant'] !== null)
                                {{ number_format($row['montant'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </strong></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="2" class="text-center">@lang('messages.Aucune donnée trouvée')</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
