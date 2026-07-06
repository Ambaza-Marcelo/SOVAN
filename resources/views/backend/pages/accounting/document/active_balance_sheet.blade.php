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
        
        .balance-sheet-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .balance-sheet-table thead th {
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
        
        .balance-sheet-table tbody td {
            font-size: 11px;
            padding: 8px 10px;
            border: 1px solid #e1e5eb;
        }
        
        .balance-sheet-table tbody tr:nth-child(even) {
            background-color: #fcfdff;
        }
        
        .balance-sheet-table tbody tr.title-row {
            background-color: #eef2f7;
            font-weight: 700;
            font-size: 12px;
        }
        
        .balance-sheet-table tbody tr.title-row td {
            font-weight: bold;
        }
        
        .balance-sheet-table tbody tr.subtotal-row {
            background-color: #eef2f7;
            font-weight: 600;
            border-top: 2px solid #d9dde2;
        }
        
        .balance-sheet-table tbody tr.subtotal-row td {
            font-weight: bold;
        }
        
        .balance-sheet-table tbody tr.total-row {
            background-color: #d9dde2;
            font-weight: 700;
            font-size: 13px;
            border-top: 3px solid #2f3a4c;
        }
        
        .balance-sheet-table tbody tr.total-row td {
            font-weight: bold;
        }
        
        .actif-col {
            text-align: left;
        }
        
        .actif-col.level-0 {
            padding-left: 5px;
        }
        
        .actif-col.level-1 {
            padding-left: 20px;
        }
        
        .actif-col.level-2 {
            padding-left: 40px;
        }
        
        .montant-col {
            text-align: right;
            padding-right: 10px;
        }
        
        .amortissement-col {
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
        'documentTitle' => 'BILAN ACTIF',
        'documentNumber' => null,
        'date' => $reportGeneratedAt ?? null
    ])
    
    <div class="report-title">@lang('messages.BILAN ACTIF')</div>
    <div class="report-date">@lang('messages.Rapport généré le') {{ $reportGeneratedAt }}</div>
    
    <div class="balance-meta">
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%;">
                <h5>@lang('messages.BILAN ACTIF')</h5>
                <p class="meta-line"><strong>@lang('messages.Structure :')</strong> {{ $companyName }}</p>
                <p class="meta-line"><strong>@lang('messages.Type de rapport :')</strong> @lang('messages.Bilan Actif')</p>
            </div>
            <div style="display: table-cell; width: 50%; text-align: right;">
                <p class="meta-line"><strong>@lang('messages.Année fiscale :')</strong> {{ $fiscalYear }}</p>
                <p class="meta-line"><strong>@lang('messages.Devise :')</strong> {{ config('app.currency', 'BIF') }}</p>
            </div>
        </div>
    </div>
    
    <table class="balance-sheet-table">
        <thead>
            <tr>
                <th style="width: 50%;">@lang('messages.ACTIFS')</th>
                <th style="width: 25%;">@lang('messages.MONTANT BRUT')</th>
                <th style="width: 25%;">@lang('messages.AMORTISSEMENT/DÉPRÉCIATION')</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($balanceSheetData as $row)
                @if($row['type'] === 'title')
                    <tr class="title-row">
                        <td class="actif-col level-{{ $row['level'] ?? 0 }}" colspan="3"><strong>{{ $row['actif'] }}</strong></td>
                    </tr>
                @elseif($row['type'] === 'subtitle')
                    <tr>
                        <td class="actif-col level-{{ $row['level'] ?? 0 }}">{{ $row['actif'] }}</td>
                        <td class="montant-col">
                            @if(isset($row['montant_brut']) && $row['montant_brut'] !== '' && $row['montant_brut'] !== null)
                                {{ number_format($row['montant_brut'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </td>
                        <td class="amortissement-col">
                            @if(isset($row['amortissement']) && $row['amortissement'] !== '' && $row['amortissement'] !== null)
                                {{ number_format($row['amortissement'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </td>
                    </tr>
                @elseif($row['type'] === 'subtotal')
                    <tr class="subtotal-row">
                        <td class="actif-col level-{{ $row['level'] ?? 0 }}"><strong>{{ $row['actif'] }}</strong></td>
                        <td class="montant-col"><strong>
                            @if(isset($row['montant_brut']) && $row['montant_brut'] !== '' && $row['montant_brut'] !== null)
                                {{ number_format($row['montant_brut'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </strong></td>
                        <td class="amortissement-col"><strong>
                            @if(isset($row['amortissement']) && $row['amortissement'] !== '' && $row['amortissement'] !== null)
                                {{ number_format($row['amortissement'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </strong></td>
                    </tr>
                @elseif($row['type'] === 'total')
                    <tr class="total-row">
                        <td class="actif-col level-{{ $row['level'] ?? 0 }}"><strong>{{ $row['actif'] }}</strong></td>
                        <td class="montant-col"><strong>
                            @if(isset($row['montant_brut']) && $row['montant_brut'] !== '' && $row['montant_brut'] !== null)
                                {{ number_format($row['montant_brut'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </strong></td>
                        <td class="amortissement-col"><strong>
                            @if(isset($row['amortissement']) && $row['amortissement'] !== '' && $row['amortissement'] !== null)
                                {{ number_format($row['amortissement'], 0, ',', ' ') }}
                            @else
                                0
                            @endif
                        </strong></td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="3" class="text-center">@lang('messages.Aucune donnée trouvée')</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
