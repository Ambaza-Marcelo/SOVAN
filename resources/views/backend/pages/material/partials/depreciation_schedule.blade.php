@php
    $scheduleData = [];
    $rows = [];
    try {
        $scheduleData = $material->depreciation_schedule ?? [];
        $rows = $scheduleData['schedule'] ?? [];
    } catch (\Throwable $e) {
        $scheduleData = [];
        $rows = [];
    }
    $fmt = function ($amount) {
        return number_format((float) $amount, 2, ',', ' ');
    };
    $currentYear = (int) date('Y');
@endphp

@if(!empty($material->is_amortizable) && !empty($rows))
<div class="depreciation-panel mt-4">
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="depreciation-kpi">
                <div class="depreciation-kpi-label">Base amortissable</div>
                <div class="depreciation-kpi-value">{{ $fmt($scheduleData['base_amortissable'] ?? 0) }} BIF</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="depreciation-kpi">
                <div class="depreciation-kpi-label">Amortissements cumulés</div>
                <div class="depreciation-kpi-value">{{ $fmt($scheduleData['amortissements_cumules'] ?? 0) }} BIF</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="depreciation-kpi depreciation-kpi-vnc">
                <div class="depreciation-kpi-label">Valeur nette comptable (VNC)</div>
                <div class="depreciation-kpi-value">{{ $fmt($scheduleData['vnc'] ?? 0) }} BIF</div>
                <div class="progress mt-2" style="height: 8px;">
                    <div
                        class="progress-bar bg-info"
                        role="progressbar"
                        style="width: {{ min(100, (float) ($scheduleData['progression_pct'] ?? 0)) }}%;"
                        aria-valuenow="{{ $scheduleData['progression_pct'] ?? 0 }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
                <small class="text-muted">Progression : {{ number_format((float) ($scheduleData['progression_pct'] ?? 0), 2, ',', ' ') }} %</small>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm text-center depreciation-schedule-table" id="depreciationScheduleTable">
            <thead class="bg-light">
                <tr>
                    <th>Année</th>
                    <th>Base amortissable</th>
                    <th>Taux</th>
                    <th>Jours (prorata)</th>
                    <th>Dotation</th>
                    <th>Cumul amortissements</th>
                    <th>VNC</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr class="{{ !empty($row['is_current_year']) || (int) $row['year'] === $currentYear ? 'depreciation-current-year' : '' }}" data-year="{{ $row['year'] }}">
                        <td>
                            <strong>{{ $row['year'] }}</strong>
                            @if(!empty($row['is_current_year']) || (int) $row['year'] === $currentYear)
                                <span class="badge badge-info ml-1">En cours</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $fmt($row['base_amortissable']) }}</td>
                        <td>{{ number_format((float) $row['taux'], 2, ',', ' ') }} %</td>
                        <td>
                            {{ (int) $row['days'] }}
                            @if(!empty($row['is_prorata']))
                                <span class="badge badge-secondary">prorata</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $fmt($row['dotation']) }}</td>
                        <td class="text-right">{{ $fmt($row['cumulative']) }}</td>
                        <td class="text-right"><strong>{{ $fmt($row['vnc']) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@elseif(!empty($material->is_amortizable))
    <div class="alert alert-info mt-3 mb-0">
        Renseignez la base d'amortissement, la date de mise en service et la durée pour générer le plan.
    </div>
@endif
