<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 6mm; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #000000;
            background: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000000;
            padding: 3px 2px;
            text-align: left;
            vertical-align: middle;
            font-size: 7.5px;
            color: #000000;
            background: #ffffff;
        }
        th {
            font-weight: bold;
            background: #f0f0f0;
            text-align: center;
        }
        .no-border td { border: none; padding: 2px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-decoration: underline;
            margin: 6px 0 8px 0;
        }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
@php
    $companyName = $setting->name ?? ($setting->company_name ?? 'MUSUMBA ELCOM');
    $logoPath = public_path('img/logo.png');
    if (!file_exists($logoPath)) {
        $logoPath = public_path('img/logo.png');
    }
    $logoSrc = null;
    if (file_exists($logoPath)) {
        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        $logoSrc = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
    }
    $fmt = function ($n) {
        return number_format((float) $n, 0, '.', ' ');
    };

    $totalBrut = 0;
    $totalInssEmp = 0;
    $totalInssEmplr = 0;
    $totalAssEmp = 0;
    $totalAssEmplr = 0;
    $totalIre = 0;
    $totalPret = 0;
    $totalSoins = 0;
    $totalAutre = 0;
    $totalRetenue = 0;
    $totalNet = 0;
    $totalBase = 0;
@endphp

<table class="no-border">
    <tr>
        <td style="width:18%; vertical-align:top;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" width="110" height="70" alt="MUSUMBA ELCOM">
            @endif
        </td>
        <td style="width:52%; vertical-align:top; font-size:9px;">
            <strong style="font-size:11px;">{{ $companyName }}</strong><br>
            @if(!empty($setting->nif)) NIF: {{ $setting->nif }} @endif
            @if(!empty($setting->rc)) | RC: {{ $setting->rc }} @endif
            <br>
            @if(!empty($setting->telephone1)) Tel: {{ $setting->telephone1 }} @endif
        </td>
        <td style="width:30%; vertical-align:top; text-align:right; font-size:9px;">
            <strong>JOURNAL DE PAIE</strong><br>
            Code: {{ $journal_paie->code ?? '' }}<br>
            DU {{ \Carbon\Carbon::parse($journal_paie->date_debut)->format('d/m/Y') }}<br>
            AU {{ \Carbon\Carbon::parse($journal_paie->date_fin)->format('d/m/Y') }}
        </td>
    </tr>
</table>

<div class="title">
    {{ strtoupper($journal_paie->title ?? 'JOURNAL DE PAIE') }}
</div>

<table>
    <thead>
        <tr>
            <th style="width:3%;">No</th>
            <th style="width:7%;">Matricule</th>
            <th style="width:12%;">Employe</th>
            <th style="width:7%;">Salaire base</th>
            <th style="width:6%;">Alloc. fam.</th>
            <th style="width:6%;">Ind. log.</th>
            <th style="width:6%;">Ind. depl.</th>
            <th style="width:6%;">Prime</th>
            <th style="width:7%;">Salaire brut</th>
            <th style="width:6%;">INSS Emp.</th>
            <th style="width:6%;">INSS Emplr</th>
            <th style="width:6%;">Ass. Mal.</th>
            <th style="width:5%;">IRE</th>
            <th style="width:5%;">Pret</th>
            <th style="width:5%;">Autres</th>
            <th style="width:6%;">Tot. ret.</th>
            <th style="width:7%;">Net a payer</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datas as $row)
            @php
                $brut = (float)$row->somme_salaire_base
                    + (float)$row->allocation_familiale
                    + (float)$row->indemnite_logement
                    + (float)$row->indemnite_deplacement
                    + (float)$row->prime_fonction;
                $retenues = (float)$row->somme_cotisation_inss
                    + (float)$row->assurance_maladie_employe
                    + (float)$row->somme_impot
                    + (float)$row->retenue_pret
                    + (float)$row->soins_medicaux
                    + (float)$row->autre_retenue;
                $net = $brut - $retenues;

                $totalBase += (float)$row->somme_salaire_base;
                $totalBrut += $brut;
                $totalInssEmp += (float)$row->somme_cotisation_inss;
                $totalInssEmplr += (float)$row->inss_employeur;
                $totalAssEmp += (float)$row->assurance_maladie_employe;
                $totalAssEmplr += (float)$row->assurance_maladie_employeur;
                $totalIre += (float)$row->somme_impot;
                $totalPret += (float)$row->retenue_pret;
                $totalSoins += (float)$row->soins_medicaux;
                $totalAutre += (float)$row->autre_retenue + (float)$row->soins_medicaux;
                $totalRetenue += $retenues;
                $totalNet += $net;
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ optional($row->employe)->matricule_no }}</td>
                <td>{{ strtoupper(optional($row->employe)->firstname.' '.optional($row->employe)->lastname) }}</td>
                <td class="text-right">{{ $fmt($row->somme_salaire_base) }}</td>
                <td class="text-right">{{ $fmt($row->allocation_familiale) }}</td>
                <td class="text-right">{{ $fmt($row->indemnite_logement) }}</td>
                <td class="text-right">{{ $fmt($row->indemnite_deplacement) }}</td>
                <td class="text-right">{{ $fmt($row->prime_fonction) }}</td>
                <td class="text-right bold">{{ $fmt($brut) }}</td>
                <td class="text-right">{{ $fmt($row->somme_cotisation_inss) }}</td>
                <td class="text-right">{{ $fmt($row->inss_employeur) }}</td>
                <td class="text-right">{{ $fmt($row->assurance_maladie_employe) }}</td>
                <td class="text-right">{{ $fmt($row->somme_impot) }}</td>
                <td class="text-right">{{ $fmt($row->retenue_pret) }}</td>
                <td class="text-right">{{ $fmt((float)$row->autre_retenue + (float)$row->soins_medicaux) }}</td>
                <td class="text-right bold">{{ $fmt($retenues) }}</td>
                <td class="text-right bold">{{ $fmt($net) }}</td>
            </tr>
        @endforeach
        <tr class="bold">
            <td colspan="3" class="text-center">TOTAL</td>
            <td class="text-right">{{ $fmt($totalBase) }}</td>
            <td colspan="4"></td>
            <td class="text-right">{{ $fmt($totalBrut) }}</td>
            <td class="text-right">{{ $fmt($totalInssEmp) }}</td>
            <td class="text-right">{{ $fmt($totalInssEmplr) }}</td>
            <td class="text-right">{{ $fmt($totalAssEmp) }}</td>
            <td class="text-right">{{ $fmt($totalIre) }}</td>
            <td class="text-right">{{ $fmt($totalPret) }}</td>
            <td class="text-right">{{ $fmt($totalAutre) }}</td>
            <td class="text-right">{{ $fmt($totalRetenue) }}</td>
            <td class="text-right">{{ $fmt($totalNet) }}</td>
        </tr>
    </tbody>
</table>

<br>
<table class="no-border">
    <tr>
        <td style="width:50%; vertical-align:top;">
            Chef Comptable et signature<br><br><br>
            ____________________________
        </td>
        <td style="width:50%; vertical-align:top; text-align:right;">
            Direction et signature<br><br><br>
            ____________________________
        </td>
    </tr>
</table>
</body>
</html>
