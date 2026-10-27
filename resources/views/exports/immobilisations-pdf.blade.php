<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Registre des immobilisations</title>
    <style>
        @page {
            size: landscape;
            margin: 18mm 12mm 16mm 12mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #222;
        }
        h1 {
            font-size: 14px;
            margin: 0 0 4px 0;
            text-align: center;
            text-transform: uppercase;
        }
        .meta {
            text-align: center;
            margin-bottom: 12px;
            font-size: 8px;
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 0.5px solid #555;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background: #d9eaf7;
            font-weight: bold;
            text-align: center;
        }
        .num { text-align: right; }
        .center { text-align: center; }
        .totals td {
            font-weight: bold;
            background: #f3f3f3;
        }
        .asset-block {
            page-break-inside: avoid;
            margin-top: 14px;
            margin-bottom: 8px;
        }
        .asset-title {
            background: #e8f5e9;
            border: 0.5px solid #555;
            padding: 4px 6px;
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 0;
        }
        .asset-title span {
            font-weight: normal;
            color: #333;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 14px 0 6px 0;
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
        }
        .muted { color: #666; font-style: italic; }
        .footer-note {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            font-size: 7px;
            color: #666;
            text-align: left;
        }
    </style>
</head>
<body>
    @include('backend.pages.document.partials._document_header', [
        'setting' => $setting,
        'documentTitle' => 'Registre des immobilisations & Plans d\'amortissement',
        'documentDate' => $exportedAt,
    ])

    <h1>Registre des immobilisations</h1>
    <div class="meta">
        Extraction : {{ $exportedAt }}
        &nbsp;|&nbsp; Exercice de référence : {{ $asOfYear }}
        &nbsp;|&nbsp; Mode : Linéaire (convention 360 jours)
    </div>

    <div class="section-title">1. Synthèse du registre</div>
    <table>
        <thead>
            <tr>
                <th style="width:9%;">Code immo</th>
                <th style="width:16%;">Désignation</th>
                <th style="width:10%;">Catégorie</th>
                <th style="width:8%;">Date acq.</th>
                <th style="width:10%;">Valeur origine HT</th>
                <th style="width:7%;">Mode</th>
                <th style="width:5%;">Durée</th>
                <th style="width:10%;">Amort. cumulés</th>
                <th style="width:9%;">VNC</th>
                <th style="width:16%;">Compte immo.</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sumOrigine = 0;
                $sumCumul = 0;
                $sumVnc = 0;
            @endphp
            @forelse($rows as $row)
                @php
                    $sumOrigine += (float) $row['valeur_origine'];
                    $sumCumul += (float) $row['amortissements_cumules'];
                    $sumVnc += (float) $row['vnc'];
                @endphp
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['designation'] }}</td>
                    <td>{{ $row['categorie'] }}</td>
                    <td class="center">{{ $row['date_acquisition'] }}</td>
                    <td class="num">{{ number_format($row['valeur_origine'], 2, ',', ' ') }}</td>
                    <td class="center">{{ $row['mode'] }}</td>
                    <td class="center">{{ $row['duree'] }}</td>
                    <td class="num">{{ number_format($row['amortissements_cumules'], 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format($row['vnc'], 2, ',', ' ') }}</td>
                    <td>{{ $row['compte_immo'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center muted">Aucune immobilisation amortissable.</td>
                </tr>
            @endforelse
            @if($rows->count() > 0)
                <tr class="totals">
                    <td colspan="4" class="center">TOTAUX</td>
                    <td class="num">{{ number_format($sumOrigine, 2, ',', ' ') }}</td>
                    <td colspan="2"></td>
                    <td class="num">{{ number_format($sumCumul, 2, ',', ' ') }}</td>
                    <td class="num">{{ number_format($sumVnc, 2, ',', ' ') }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title" style="page-break-before: always;">2. Plans d'amortissement détaillés</div>

    @forelse($rows as $asset)
        <div class="asset-block">
            <div class="asset-title">
                {{ $asset['code'] }} — {{ $asset['designation'] }}
                &nbsp;|&nbsp; <span>{{ $asset['categorie'] }}</span>
                &nbsp;|&nbsp; <span>Acq. {{ $asset['date_acquisition'] }}</span>
                &nbsp;|&nbsp; <span>{{ $asset['mode'] }} / {{ $asset['duree'] }} an(s)</span>
                &nbsp;|&nbsp; <span>VO HT : {{ number_format($asset['valeur_origine'], 2, ',', ' ') }}</span>
            </div>
            @if(empty($asset['plan']))
                <p class="muted" style="margin:4px 0 0 0;">Plan non disponible — date de mise en service non renseignée.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:12%;">Année</th>
                            <th style="width:20%;">Base de calcul</th>
                            <th style="width:20%;">Annuité</th>
                            <th style="width:22%;">Amortissements cumulés</th>
                            <th style="width:18%;">VNC fin de période</th>
                            <th style="width:8%;">Jours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sumAnnuite = 0; @endphp
                        @foreach($asset['plan'] as $line)
                            @php $sumAnnuite += (float) $line['annuite']; @endphp
                            <tr @if(!empty($line['is_current_year'])) style="background:#fff8e1;" @endif>
                                <td class="center">{{ $line['year'] }}</td>
                                <td class="num">{{ number_format($line['base'], 2, ',', ' ') }}</td>
                                <td class="num">{{ number_format($line['annuite'], 2, ',', ' ') }}</td>
                                <td class="num">{{ number_format($line['cumul'], 2, ',', ' ') }}</td>
                                <td class="num">{{ number_format($line['vnc'], 2, ',', ' ') }}</td>
                                <td class="center">{{ $line['days'] }}@if(!empty($line['is_prorata']))*@endif</td>
                            </tr>
                        @endforeach
                        <tr class="totals">
                            <td class="center">TOTAL</td>
                            <td></td>
                            <td class="num">{{ number_format($sumAnnuite, 2, ',', ' ') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    @empty
        <p class="muted">Aucun plan à afficher.</p>
    @endforelse

    <div class="footer-note">
        * Prorata temporis (convention 360 jours). Document généré automatiquement — SOVAN.
    </div>
</body>
</html>
