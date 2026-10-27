<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    @include('backend.pages.document.partials._document_styles')
    <style>
        .kpi-row { width: 100%; margin: 12px 0 18px; }
        .kpi-row td { border: 1px solid #ddd; padding: 8px 10px; vertical-align: top; }
        .kpi-label { font-size: 10px; color: #666; text-transform: uppercase; }
        .kpi-value { font-size: 14px; font-weight: bold; margin-top: 4px; }
    </style>
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
    @include('backend.pages.document.partials._document_header')

    <h3 class="doc-title">PLAN D'AMORTISSEMENT LINÉAIRE</h3>
    <div class="doc-meta">
        <strong>Article :</strong> {{ $material->name }} ({{ $material->code }})
        &nbsp;|&nbsp; <strong>Mise en service :</strong> {{ optional($material->service_start_date)->format('d/m/Y') ?? 'N/A' }}
        &nbsp;|&nbsp; <strong>Durée :</strong> {{ $material->duration_years }} an(s)
        &nbsp;|&nbsp; <strong>Taux :</strong> {{ number_format($schedule['taux'] ?? 0, 2, ',', ' ') }} %
    </div>
    <div class="doc-meta">
        <strong>Charge :</strong>
        @if($material->depreciationExpenseAccount)
            {{ $material->depreciationExpenseAccount->account_number }} - {{ $material->depreciationExpenseAccount->account_name }}
        @else
            N/A
        @endif
        &nbsp;|&nbsp;
        <strong>Cumulés :</strong>
        @if($material->depreciationAssetAccount)
            {{ $material->depreciationAssetAccount->account_number }} - {{ $material->depreciationAssetAccount->account_name }}
        @else
            N/A
        @endif
    </div>

    <table class="kpi-row">
        <tr>
            <td width="33%">
                <div class="kpi-label">Base amortissable</div>
                <div class="kpi-value">{{ number_format($schedule['base_amortissable'] ?? 0, 2, ',', ' ') }}</div>
            </td>
            <td width="33%">
                <div class="kpi-label">Amortissements cumulés</div>
                <div class="kpi-value">{{ number_format($schedule['amortissements_cumules'] ?? 0, 2, ',', ' ') }}</div>
            </td>
            <td width="34%">
                <div class="kpi-label">VNC</div>
                <div class="kpi-value">{{ number_format($schedule['vnc'] ?? 0, 2, ',', ' ') }}</div>
            </td>
        </tr>
    </table>

    <table class="doc-table">
        <thead>
            <tr>
                <th>Année</th>
                <th class="text-right">Base amortissable</th>
                <th class="text-right">Taux %</th>
                <th class="text-right">Jours</th>
                <th class="text-right">Dotation</th>
                <th class="text-right">Cumul</th>
                <th class="text-right">VNC</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($schedule['schedule'] ?? []) as $row)
                <tr>
                    <td>{{ $row['year'] }}{{ !empty($row['is_current_year']) ? ' *' : '' }}</td>
                    <td class="text-right">{{ number_format($row['base_amortissable'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($row['taux'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ $row['days'] ?? 0 }}</td>
                    <td class="text-right">{{ number_format($row['dotation'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($row['cumulative'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($row['vnc'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size: 10px; color: #666;">* Année en cours</p>
</body>
</html>
