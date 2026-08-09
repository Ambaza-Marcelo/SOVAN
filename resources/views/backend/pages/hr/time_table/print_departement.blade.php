<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Horaire du personnel - {{ $departement->name }}</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .shift-a {
            background-color: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        .shift-b {
            background-color: #2196F3;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        .shift-off {
            background-color: #9E9E9E;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
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
        <h3>HORAIRE HEBDOMADAIRE - {{ strtoupper($departement->name) }}</h3>
        <p>
            Semaine du {{ $weekDates[0]->format('d/m/Y') }} au {{ $weekDates[6]->format('d/m/Y') }}
        </p>
    </div>

    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            Imprimer
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Fermer
        </button>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">NOMS</th>
                @foreach($weekDates as $date)
                    <th>
                        {{ strtoupper($date->locale('fr')->dayName) }}<br>
                        <small>{{ $date->format('d/m/Y') }}</small>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($employes as $employe)
                <tr>
                    <td style="text-align: left; font-weight: bold;">
                        {{ strtoupper($employe->lastname . ' ' . $employe->firstname) }}
                    </td>
                    @foreach($weekDates as $date)
                        @php
                            $dateStr = $date->format('Y-m-d');
                            $timeTable = $timeTables->get($employe->id, collect())->get($dateStr);
                        @endphp
                        <td>
                            @if($timeTable)
                                @if($timeTable->shift_name == 'A')
                                    <span class="shift-a">A<br><small>7h30-16h00</small></span>
                                @elseif($timeTable->shift_name == 'B')
                                    <span class="shift-b">B<br><small>15h00-Close</small></span>
                                @else
                                    <span class="shift-off">{{ $timeTable->shift_name }}</span>
                                @endif
                            @else
                                <span class="shift-off">OFF</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px;">
        <p><strong>Légende:</strong></p>
        <p>
            <span class="shift-a">SHIFT A (7h30-16h00)</span>
            <span class="shift-b" style="margin-left: 10px;">SHIFT B (15h00-Close)</span>
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

