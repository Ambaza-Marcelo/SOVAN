
@extends('backend.layouts.master')

@section('title')
@lang('Prendre un congé payé') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('Prendre un congé payé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('Prendre un congé payé')</span></li>
                </ul>
            </div>
        </div>
        <div class="col-sm-6 clearfix">
            @include('backend.layouts.partials.logout')
        </div>
    </div>
</div>
<!-- page title area end -->
<div class="main-content-inner">
    <div class="row">
        <!-- data table start -->
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Prendre un congé payé</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-take-conge-payes.store') }}" method="POST">
                        @csrf
                    <input type="hidden" name="company_id" value="{{ $company_id }}">
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
                                'placeholder' => 'Tapez nom ou matricule...',
                            ])
                        </div>
                        </div>
                    </div>
                    <p class="text-muted small">Si l'employé a déjà un congé en cours, la date de début du nouveau congé doit être <strong>strictement supérieure</strong> à la date de fin de son dernier congé (date suivante possible).</p>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_debut">@lang('Date début')</label>
                                <input type="date" class="form-control" name="date_heure_debut" id="date_heure_debut">
                                <small id="min_date_hint" class="form-text text-info d-none"></small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_fin">@lang('Date fin')</label>
                                <input type="date" class="form-control" name="date_heure_fin" id="date_heure_fin">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group" id="nbre_jours_conge_paye">

                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="nbre_jours_conge_sollicite">@lang('Nbre Jrs Congé Sollicité')</label>
                                <input type="number" class="form-control" name="nbre_jours_conge_sollicite" id="nbre_jours_conge_sollicite" min="1" max="15">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                           <div class="form-group" id="nbre_jours_conge_pris">

                            </div>
                        </div> 
                        <div class="col-sm-6">
                           <div class="form-group" id="nbre_jours_conge_restant">
                                
                            </div>
                        </div> 
                    </div>
 
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
@include('backend.pages.hr.partials.employe_autocomplete_scripts')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript">
    let congeState = null;

    function resetCongeDisplays() {
        congeState = null;
        $('#nbre_jours_conge_paye').html('');
        $('#nbre_jours_conge_pris').html('');
        $('#nbre_jours_conge_restant').html('');
    }

    function renderCongeContainers() {
        $('#nbre_jours_conge_paye').html('<label>Nbre Jrs Congé Annuel</label><input type="number" name="nbre_jours_conge_paye" class="form-control" id="nbre_jours_conge_paye_value" readonly>');
        $('#nbre_jours_conge_pris').html('<label>Nbre Jrs Congé Pris</label><input type="number" name="nbre_jours_conge_pris" class="form-control" id="nbre_jours_conge_pris_value" readonly>');
        $('#nbre_jours_conge_restant').html('<label>Nbre Jrs Congé Restant</label><input type="number" name="nbre_jours_conge_restant" class="form-control" id="nbre_jours_conge_restant_value" readonly>');
    }

    function setCongeState(data) {
        if (!data) {
            resetCongeDisplays();
            return;
        }

        const paye = Number(data.nbre_jours_conge_paye) || 0;
        const pris = Number(data.nbre_jours_conge_pris) || 0;
        const restant = Number(data.nbre_jours_conge_restant) || 0;

        congeState = { paye, pris, restant };
        renderCongeContainers();
        $('#nbre_jours_conge_paye_value').val(paye);

        // Met à jour les bornes possibles sur le nombre de jours sollicités
        const maxDays = getMaxSollicitableDays();
        $('#nbre_jours_conge_sollicite')
            .attr('min', MIN_CONGE_DAYS)
            .attr('max', maxDays);

        updateCongeDerivedFields();
    }

    function clearMinDateDebut() {
        $('#date_heure_debut').removeAttr('min');
        $('#min_date_hint').addClass('d-none').text('');
    }

    function fetchMinDateDebutForEmploye(idEmploye) {
        if (!idEmploye) {
            clearMinDateDebut();
            return;
        }
        $.ajax({
            url: "{{ route('admin.hr-take-conge-payes.fetch-min-date-debut') }}",
            type: "POST",
            data: { employe_id: idEmploye, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function (result) {
                if (result.min_date_debut) {
                    $('#date_heure_debut').attr('min', result.min_date_debut);
                    $('#min_date_hint').removeClass('d-none').text('Date minimale (après dernier congé) : ' + result.min_date_debut);
                    var current = $('#date_heure_debut').val();
                    if (current && current < result.min_date_debut) {
                        $('#date_heure_debut').val('');
                        $('#date_heure_fin').val('');
                        updateCongeSollicite();
                    }
                } else {
                    clearMinDateDebut();
                }
            },
            error: function () {
                clearMinDateDebut();
            }
        });
    }

    // Function to fetch employee data from database
    function fetchEmployeData(idEmploye) {
        if (!idEmploye) {
            resetCongeDisplays();
            return;
        }

        $.ajax({
            url: "{{route('admin.hr-take-conge-payes.fetch')}}",
            type: "POST",
            data: {
                employe_id: idEmploye,
                _token: '{{csrf_token()}}'
            },
            dataType: 'json',
            success: function (result) {
                if (!result.data || !result.data.length) {
                    resetCongeDisplays();
                    return;
                }

                setCongeState(result.data[0]);
            }
        });
    }

    function onEmployeSelected(idEmploye) {
        if (!idEmploye) {
            resetCongeDisplays();
            clearMinDateDebut();
            return;
        }

        fetchEmployeData(idEmploye);
        fetchMinDateDebutForEmploye(idEmploye);
    }

    $(document).ready(function() {
        var createEmployees = [
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
            employees: createEmployees,
            allowEmpty: false,
            onSelect: onEmployeSelected
        });
    });

    const MIN_CONGE_DAYS = 1;
    const MAX_CONGE_DAYS = 15;
    const msInDay = 1000 * 60 * 60 * 24;

    function getMaxSollicitableDays() {
        if (!congeState) {
            return MAX_CONGE_DAYS;
        }

        const restant = Number(congeState.restant) || 0;
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

    function setStartDateConstraints() {
        const today = new Date();
        const minStart = today; // aujourd'hui mini côté front, le back garde la validation finale
        $('#date_heure_debut').attr('min', formatDateToInput(minStart));
    }

    function setEndDateConstraints() {
        const start = $('#date_heure_debut').val();
        if (!start) {
            $('#date_heure_fin').attr('min', '');
            return;
        }

        const startDate = new Date(start);

        if (isNaN(startDate.getTime())) {
            $('#date_heure_fin').attr('min', '');
            return;
        }

        // Date de fin minimale = date de début (1 jour de congé)
        const minEndDate = new Date(startDate.getTime() + (MIN_CONGE_DAYS - 1) * msInDay);
        const minEndFormatted = formatDateToInput(minEndDate);
        $('#date_heure_fin').attr('min', minEndFormatted);

        const currentEnd = $('#date_heure_fin').val();
        if (currentEnd) {
            const currentEndDate = new Date(currentEnd);
            if (currentEndDate < minEndDate) {
                $('#date_heure_fin').val(minEndFormatted);
            }
        }
    }

    function updateCongeSollicite() {
        const start = $('#date_heure_debut').val();
        const end = $('#date_heure_fin').val();

        if (!start || !end) {
            $('#nbre_jours_conge_sollicite').val('');
            updateCongeDerivedFields();
            return;
        }

        const startDate = new Date(start);
        const endDate = new Date(end);

        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime()) || endDate < startDate) {
            $('#nbre_jours_conge_sollicite').val('');
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
            $('#nbre_jours_conge_sollicite').val('');
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

        const startDate = new Date(start);
        if (isNaN(startDate.getTime())) {
            updateCongeDerivedFields();
            return;
        }

        const endDate = new Date(startDate.getTime() + (jours - 1) * msInDay);
        $('#date_heure_fin').val(formatDateToInput(endDate));
        setEndDateConstraints();
        updateCongeDerivedFields();
    }

    function updateCongeDerivedFields() {
        const prisInput = $('#nbre_jours_conge_pris_value');
        const restantInput = $('#nbre_jours_conge_restant_value');

        if (!prisInput.length || !restantInput.length || !congeState) {
            return;
        }

        let sollicite = Number($('#nbre_jours_conge_sollicite').val());

        if (isNaN(sollicite) || sollicite <= 0) {
            prisInput.val(congeState.pris);
            restantInput.val(congeState.restant);
            return;
        }

        // pris = déjà pris avant cette demande (ne change pas)
        // restant = disponible avant − sollicité
        // Identité : annuel = pris + sollicité + restant
        const updatedPris = Math.max(0, Number(congeState.pris) || 0);
        const updatedRestant = Math.max(0, (Number(congeState.restant) || 0) - sollicite);

        prisInput.val(updatedPris);
        restantInput.val(updatedRestant);
    }

    setStartDateConstraints();
    setEndDateConstraints();

    $('#date_heure_debut').on('change', function () {
        setEndDateConstraints();
        updateCongeSollicite();
    });

    $('#date_heure_fin').on('change', updateCongeSollicite);
    $('#nbre_jours_conge_sollicite').on('input', updateEndDateFromSollicite);

</script>
@endsection
