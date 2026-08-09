
@extends('backend.layouts.master')

@section('title')
@lang('Modifier un congé payé') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection

@php
    $selectedEmployeId = old('employe_id', $data->employe_id);
    $selectedEmployeLabel = optional($data->employe)->display_label;
    if (old('employe_id') && $employes->firstWhere('id', (int) old('employe_id'))) {
        $selectedEmployeLabel = $employes->firstWhere('id', (int) old('employe_id'))->display_label;
    }
    $dateDebut = old('date_heure_debut', $data->date_heure_debut ? \Carbon\Carbon::parse($data->date_heure_debut)->format('Y-m-d') : '');
    $dateFin = old('date_heure_fin', $data->date_heure_fin ? \Carbon\Carbon::parse($data->date_heure_fin)->format('Y-m-d') : '');
    $nbreSollicite = old('nbre_jours_conge_sollicite', $amounts['sollicite'] ?? $data->nbre_jours_conge_sollicite);
    $nbrePaye = old('nbre_jours_conge_paye', $amounts['paye'] ?? $data->nbre_jours_conge_paye);
    $nbrePris = old('nbre_jours_conge_pris', $amounts['pris'] ?? $data->nbre_jours_conge_pris);
    $nbreRestant = old('nbre_jours_conge_restant', $amounts['restant'] ?? $data->nbre_jours_conge_restant);
    $basePris = (int) ($balance['base_pris'] ?? $nbrePris);
    $baseRestant = (int) ($balance['base_restant'] ?? ((int) $nbreRestant + (int) $nbreSollicite));
    $basePaye = (int) ($balance['paye'] ?? $nbrePaye);
@endphp

@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Modifier un congé payé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('Modifier un congé payé')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>
<div class="main-content-inner">
    <div class="row">
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Modifier un congé payé</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-take-conge-payes.update',$data->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="form-group">
                            <label for="employe_search">@lang('Employé')</label>
                            @include('backend.pages.hr.partials.employe_autocomplete_field', [
                                'employes' => $employes,
                                'inputId' => 'employe_search',
                                'hiddenId' => 'employe_id',
                                'hiddenName' => 'employe_id',
                                'listId' => 'employe_search-list',
                                'selectedEmployeId' => $selectedEmployeId,
                                'initialLabel' => $selectedEmployeLabel,
                                'placeholder' => 'Tapez nom ou matricule...',
                            ])
                        </div>
                        </div>
                    </div>
                    <p class="text-muted small">Si l'employé a déjà un autre congé en cours, la date de début doit être <strong>strictement supérieure</strong> à la date de fin de son dernier congé.</p>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_debut">@lang('Date début')</label>
                                <input type="date" class="form-control" name="date_heure_debut" id="date_heure_debut" value="{{ $dateDebut }}" required>
                                <small id="min_date_hint" class="form-text text-info d-none"></small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_fin">@lang('Date fin')</label>
                                <input type="date" class="form-control" name="date_heure_fin" id="date_heure_fin" value="{{ $dateFin }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="nbre_jours_conge_paye_value">Nbre Jrs Congé Annuel</label>
                                <input type="number" name="nbre_jours_conge_paye" class="form-control" id="nbre_jours_conge_paye_value" value="{{ $nbrePaye }}" readonly>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="nbre_jours_conge_sollicite">@lang('Nbre Jrs Congé Sollicité')</label>
                                <input type="number" class="form-control" name="nbre_jours_conge_sollicite" id="nbre_jours_conge_sollicite" value="{{ $nbreSollicite }}" min="1" max="15" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="nbre_jours_conge_pris_value">Nbre Jrs Congé Pris</label>
                                <input type="number" name="nbre_jours_conge_pris" class="form-control" id="nbre_jours_conge_pris_value" value="{{ $nbrePris }}" readonly>
                                <small class="form-text text-muted">Jours déjà pris (hors cette demande)</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="nbre_jours_conge_restant_value">Nbre Jrs Congé Restant</label>
                                <input type="number" name="nbre_jours_conge_restant" class="form-control" id="nbre_jours_conge_restant_value" value="{{ $nbreRestant }}" readonly>
                                <small class="form-text text-muted">Après déduction de cette demande</small>
                            </div>
                        </div>
                    </div>
 
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
@include('backend.pages.hr.partials.employe_autocomplete_scripts')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript">
    const MIN_CONGE_DAYS = 1;
    const MAX_CONGE_DAYS = 15;
    const msInDay = 1000 * 60 * 60 * 24;
    const currentTakeCongePayeId = {{ (int) $data->id }};

    // Solde de référence AVANT cette demande (source de vérité serveur)
    let congeState = {
        paye: {{ (int) $basePaye }},
        basePris: {{ (int) $basePris }},
        baseRestant: {{ (int) $baseRestant }}
    };

    function resetCongeDisplays() {
        congeState = null;
        $('#nbre_jours_conge_paye_value').val('');
        $('#nbre_jours_conge_pris_value').val('');
        $('#nbre_jours_conge_restant_value').val('');
    }

    function setCongeStateFromBalance(data) {
        if (!data) {
            resetCongeDisplays();
            return;
        }

        congeState = {
            paye: Number(data.nbre_jours_conge_paye) || 0,
            basePris: Number(data.nbre_jours_conge_pris) || 0,
            baseRestant: Number(data.nbre_jours_conge_restant) || 0
        };

        $('#nbre_jours_conge_paye_value').val(congeState.paye);
        $('#nbre_jours_conge_sollicite')
            .attr('min', MIN_CONGE_DAYS)
            .attr('max', getMaxSollicitableDays());

        updateCongeDerivedFields();
    }

    function clearMinDateDebut() {
        $('#min_date_hint').addClass('d-none').text('');
    }

    function fetchMinDateDebutForEmploye(idEmploye, preserveDates) {
        if (!idEmploye) {
            $('#date_heure_debut').removeAttr('min');
            clearMinDateDebut();
            return;
        }
        $.ajax({
            url: "{{ route('admin.hr-take-conge-payes.fetch-min-date-debut') }}",
            type: "POST",
            data: { employe_id: idEmploye, exclude_id: currentTakeCongePayeId, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function (result) {
                if (result.min_date_debut) {
                    $('#date_heure_debut').attr('min', result.min_date_debut);
                    $('#min_date_hint').removeClass('d-none').text('Date minimale (après dernier autre congé) : ' + result.min_date_debut);
                    if (!preserveDates) {
                        var current = $('#date_heure_debut').val();
                        if (current && current < result.min_date_debut) {
                            $('#date_heure_debut').val('');
                            $('#date_heure_fin').val('');
                            updateCongeSollicite();
                        }
                    }
                } else {
                    $('#date_heure_debut').removeAttr('min');
                    clearMinDateDebut();
                }
            },
            error: function () {
                clearMinDateDebut();
            }
        });
    }

    function fetchEmployeData(idEmploye) {
        if (!idEmploye) {
            resetCongeDisplays();
            return;
        }

        $.ajax({
            url: "{{ route('admin.hr-take-conge-payes.fetch') }}",
            type: "POST",
            data: {
                employe_id: idEmploye,
                exclude_id: currentTakeCongePayeId,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function (result) {
                if (!result.data || !result.data.length) {
                    resetCongeDisplays();
                    return;
                }
                setCongeStateFromBalance(result.data[0]);
            }
        });
    }

    function onEmployeSelected(idEmploye) {
        if (!idEmploye) {
            resetCongeDisplays();
            $('#date_heure_debut').removeAttr('min');
            clearMinDateDebut();
            return;
        }

        fetchMinDateDebutForEmploye(idEmploye, false);
        fetchEmployeData(idEmploye);
    }

    function getMaxSollicitableDays() {
        if (!congeState) {
            return MAX_CONGE_DAYS;
        }

        const restant = Number(congeState.baseRestant) || 0;
        if (restant <= 0) {
            return 0;
        }

        return Math.min(restant, MAX_CONGE_DAYS);
    }

    function formatDateToInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function setEndDateConstraints() {
        const start = $('#date_heure_debut').val();
        if (!start) {
            $('#date_heure_fin').attr('min', '');
            return;
        }

        const startDate = new Date(start + 'T00:00:00');

        if (isNaN(startDate.getTime())) {
            $('#date_heure_fin').attr('min', '');
            return;
        }

        const minEndDate = new Date(startDate.getTime() + (MIN_CONGE_DAYS - 1) * msInDay);
        $('#date_heure_fin').attr('min', formatDateToInput(minEndDate));
    }

    function updateCongeSollicite() {
        const start = $('#date_heure_debut').val();
        const end = $('#date_heure_fin').val();

        if (!start || !end) {
            updateCongeDerivedFields();
            return;
        }

        const startDate = new Date(start + 'T00:00:00');
        const endDate = new Date(end + 'T00:00:00');

        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime()) || endDate < startDate) {
            updateCongeDerivedFields();
            return;
        }

        const diff = Math.round((endDate - startDate) / msInDay) + 1;
        const maxDays = getMaxSollicitableDays() || MAX_CONGE_DAYS;
        const clampedDiff = Math.max(MIN_CONGE_DAYS, Math.min(diff, maxDays));
        $('#nbre_jours_conge_sollicite').val(clampedDiff);
        updateCongeDerivedFields();
    }

    function updateEndDateFromSollicite() {
        const start = $('#date_heure_debut').val();
        let jours = Number($('#nbre_jours_conge_sollicite').val());

        if (!start || isNaN(jours)) {
            updateCongeDerivedFields();
            return;
        }

        const maxDays = getMaxSollicitableDays() || MAX_CONGE_DAYS;
        if (maxDays === 0) {
            updateCongeDerivedFields();
            return;
        }

        if (jours < MIN_CONGE_DAYS) {
            jours = MIN_CONGE_DAYS;
        }
        if (jours > maxDays) {
            jours = maxDays;
        }
        $('#nbre_jours_conge_sollicite').val(jours);

        const startDate = new Date(start + 'T00:00:00');
        if (isNaN(startDate.getTime())) {
            updateCongeDerivedFields();
            return;
        }

        const endDate = new Date(startDate.getTime() + (jours - 1) * msInDay);
        $('#date_heure_fin').val(formatDateToInput(endDate));
        setEndDateConstraints();
        updateCongeDerivedFields();
    }

    /**
     * Identité fiable :
     *   annuel = pris (avant) + sollicité + restant (après)
     * Pris n'évolue pas avec la demande en cours.
     * Restant = disponible_avant - sollicité.
     */
    function updateCongeDerivedFields() {
        if (!congeState) {
            return;
        }

        let sollicite = Number($('#nbre_jours_conge_sollicite').val());
        if (isNaN(sollicite) || sollicite < 0) {
            sollicite = 0;
        }

        const maxDays = getMaxSollicitableDays();
        if (sollicite > maxDays && maxDays > 0) {
            sollicite = maxDays;
            $('#nbre_jours_conge_sollicite').val(sollicite);
        }

        const pris = Math.max(0, Number(congeState.basePris) || 0);
        const restant = Math.max(0, (Number(congeState.baseRestant) || 0) - sollicite);

        $('#nbre_jours_conge_paye_value').val(congeState.paye);
        $('#nbre_jours_conge_pris_value').val(pris);
        $('#nbre_jours_conge_restant_value').val(restant);
        $('#nbre_jours_conge_sollicite').attr('max', maxDays || MAX_CONGE_DAYS);
    }

    $(document).ready(function () {
        var editEmployees = [
            @foreach($employes as $employe)
            {
                id: {{ $employe->id }},
                name: {!! json_encode(trim($employe->firstname . ' ' . $employe->lastname)) !!},
                matricule: {!! json_encode($employe->matricule_no ?? '') !!}
            }@if(!$loop->last),@endif
            @endforeach
        ];

        initHrEmployeAutocomplete({
            inputId: 'employe_search',
            hiddenId: 'employe_id',
            listId: 'employe_search-list',
            employees: editEmployees,
            allowEmpty: false,
            onSelect: onEmployeSelected
        });

        setEndDateConstraints();
        updateCongeDerivedFields();

        var initialEmployeId = $('#employe_id').val();
        if (initialEmployeId) {
            fetchMinDateDebutForEmploye(initialEmployeId, true);
            // Recalcule depuis le serveur pour fiabiliser pris/restant
            fetchEmployeData(initialEmployeId);
        }

        $('#date_heure_debut').on('change', function () {
            setEndDateConstraints();
            updateCongeSollicite();
        });

        $('#date_heure_fin').on('change', updateCongeSollicite);
        $('#nbre_jours_conge_sollicite').on('input', updateEndDateFromSollicite);
    });

</script>
@endsection
