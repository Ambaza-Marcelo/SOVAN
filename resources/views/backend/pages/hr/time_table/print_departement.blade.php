<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.schedule_department_title', ['department' => $departement->name]) }}</title>
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
            <h2>{{ $setting->name ?? config('app.name', 'Entreprise') }}</h2>
            <p>{{ $setting->commune ?? '' }}-{{ $setting->zone ?? '' }}, {{ $setting->rue ?? '' }}</p>
            <p>Tél: {{ $setting->telephone1 ?? '' }}-{{ $setting->telephone2 ?? '' }}</p>
        @endif
        <h3>{{ __('messages.schedule_weekly_department', ['department' => strtoupper($departement->name)]) }}</h3>
        <p>
            {{ __('messages.schedule_week_range', ['start' => $weekDates[0]->format('d/m/Y'), 'end' => $weekDates[6]->format('d/m/Y')]) }}
        </p>
    </div>

    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            {{ __('messages.schedule_print') }}
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            {{ __('messages.schedule_close') }}
        </button>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">{{ __('messages.schedule_names') }}</th>
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
                                    <span class="shift-a">A<br><small>@lang('messages.7h30-16h00')</small></span>
                                @elseif($timeTable->shift_name == 'B')
                                    <span class="shift-b">B<br><small>@lang('messages.15h00-Close')</small></span>
                                @else
                                    <span class="shift-off">{{ $timeTable->shift_name }}</span>
                                @endif
                            @else
                                <span class="shift-off">@lang('messages.OFF')</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px;">
        <p><strong>{{ __('messages.schedule_legend') }}:</strong></p>
        <p>
            <span class="shift-a">{{ __('messages.schedule_shift_a', ['start' => '7h30', 'end' => '16h00']) }}</span>
            <span class="shift-b" style="margin-left: 10px;">{{ __('messages.schedule_shift_b', ['start' => '15h00', 'end' => 'Close']) }}</span>
            <span class="shift-off" style="margin-left: 10px;">{{ __('messages.schedule_off_with_rest') }}</span>
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

