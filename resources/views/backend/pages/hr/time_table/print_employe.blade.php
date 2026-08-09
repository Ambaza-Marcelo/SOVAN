<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Horaire - {{ $employe->lastname }} {{ $employe->firstname }}</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .shift-a {
            background-color: #4CAF50;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .shift-b {
            background-color: #2196F3;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .shift-off {
            background-color: #9E9E9E;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 14px;
        }
        .info-table {
            margin-bottom: 30px;
        }
        .info-table td {
            border: none;
            text-align: left;
            padding: 5px;
        }
        .info-table th {
            border: none;
            text-align: left;
            padding: 5px;
            width: 150px;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
            body {
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        @if($setting)
            <h2>{{ $setting->name ?? 'Entreprise' }}</h2>
            <p>{{ $setting->commune ?? '' }}-{{ $setting->zone ?? '' }}, {{ $setting->rue ?? '' }}</p>
            <p>Tél: {{ $setting->telephone1 ?? '' }}-{{ $setting->telephone2 ?? '' }}</p>
        @endif
        <h3>HORAIRE HEBDOMADAIRE INDIVIDUEL</h3>
    </div>

    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            Imprimer
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Fermer
        </button>
    </div>

    <table class="info-table">
        <tr>
            <th>Nom et Prénom:</th>
            <td><strong>{{ strtoupper($employe->lastname . ' ' . $employe->firstname) }}</strong></td>
        </tr>
        <tr>
            <th>Matricule:</th>
            <td>{{ $employe->matricule_no ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Département:</th>
            <td>{{ $employe->departement->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Service:</th>
            <td>{{ $employe->service->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Fonction:</th>
            <td>{{ $employe->fonction->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Période:</th>
            <td>Semaine du {{ $weekDates[0]->format('d/m/Y') }} au {{ $weekDates[6]->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Jour</th>
                <th style="width: 20%;">Date</th>
                <th style="width: 50%;">Shift</th>
            </tr>
        </thead>
        <tbody>
            @foreach($weekDates as $date)
                @php
                    $dateStr = $date->format('Y-m-d');
                    $timeTable = $timeTables->get($dateStr);
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ strtoupper($date->locale('fr')->dayName) }}</td>
                    <td>{{ $date->format('d/m/Y') }}</td>
                    <td>
                        @if($timeTable)
                            @if($timeTable->shift_name == 'A')
                                <span class="shift-a">SHIFT A (7h30 - 16h00)</span>
                            @elseif($timeTable->shift_name == 'B')
                                <span class="shift-b">SHIFT B (15h00 - Fermeture)</span>
                            @else
                                <span class="shift-off">{{ $timeTable->shift_name }}</span>
                            @endif
                        @else
                            <span class="shift-off">OFF (Repos)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 40px;">
        <table class="info-table">
            <tr>
                <th>Signature de l'employé:</th>
                <td style="border-bottom: 1px solid #000; min-height: 40px;">
                    <br><br>
                </td>
            </tr>
            <tr>
                <th>Date:</th>
                <td style="border-bottom: 1px solid #000;">
                    {{ date('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 30px; font-size: 11px; text-align: center;">
        <p><strong>Légende:</strong></p>
        <p>
            <span class="shift-a">SHIFT A (7h30-16h00)</span>
            <span class="shift-b" style="margin-left: 10px;">SHIFT B (15h00-Fermeture)</span>
            <span class="shift-off" style="margin-left: 10px;">OFF (Repos)</span>
        </p>
    </div>

    <script>
        window.onload = function() {
            // Auto-print on load (optional, comment if not needed)
            // window.print();
        };
    </script>
</body>
</html>

