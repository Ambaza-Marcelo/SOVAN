<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">
        table, tr, th, td {
            border-collapse: collapse;
            border: 1px solid black;
            font-size: 11px;
        }
        th, td {
            padding: 4px 6px;
        }
        th {
            background-color: #f0f0f0;
        }
        body {
            font-size: 12px;
        }
    </style>
</head>
<body>
@include('backend.pages.document.partials.standard_document_header_contact', ['setting' => $setting ?? null])
@include('backend.pages.document.partials.standard_document_header_spacer')

<h3 style="text-align: center; text-decoration: underline;">RAPPORT DES MOUVEMENTS DE CONGÉS PAYÉS</h3>

<p>
    <strong>Société :</strong> {{ $company->name ?? '—' }}<br>
    <strong>Période :</strong>
    @if(!empty($filters['date_debut']) || !empty($filters['date_fin']))
        @if(!empty($filters['date_debut'])) Du {{ \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y') }} @endif
        @if(!empty($filters['date_fin'])) au {{ \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y') }} @endif
    @else
        Toutes les dates
    @endif
    <br>
    <strong>Employé :</strong> {{ $employeLabel }}<br>
    <strong>État :</strong> Approuvés uniquement
</p>

<table style="width: 100%;">
    <thead>
        <tr>
            <th>N°</th>
            <th>Code</th>
            <th>Employé</th>
            <th>Matricule</th>
            <th>Date début</th>
            <th>Date fin</th>
            <th>Jrs annuel</th>
            <th>Jrs sollicités</th>
            <th>Jrs pris</th>
            <th>Jrs restants</th>
            <th>État</th>
            <th>Date saisie</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $record)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $record->code }}</td>
            <td>{{ optional($record->employe)->display_label }}</td>
            <td>{{ optional($record->employe)->matricule_no }}</td>
            <td>{{ $record->date_heure_debut ? \Carbon\Carbon::parse($record->date_heure_debut)->format('d/m/Y') : '' }}</td>
            <td>{{ $record->date_heure_fin ? \Carbon\Carbon::parse($record->date_heure_fin)->format('d/m/Y') : '' }}</td>
            <td>{{ $record->nbre_jours_conge_paye }}</td>
            <td>{{ $record->nbre_jours_conge_sollicite }}</td>
            <td>{{ $record->nbre_jours_conge_pris }}</td>
            <td>{{ $record->nbre_jours_conge_restant }}</td>
            <td>
                @if($record->etat == 1) Encours
                @elseif($record->etat == 2) Validé
                @elseif($record->etat == 3) Confirmé
                @elseif($record->etat == 4) Approuvé
                @elseif($record->etat == -1) Rejeté
                @else {{ $record->etat }}
                @endif
            </td>
            <td>{{ $record->created_at ? \Carbon\Carbon::parse($record->created_at)->format('d/m/Y H:i') : '' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="12" style="text-align: center;">Aucun mouvement trouvé pour les critères sélectionnés.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<p style="margin-top: 20px;">
    <small>Généré le {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} — Total : {{ $records->count() }} mouvement(s)</small>
</p>
</body>
</html>
