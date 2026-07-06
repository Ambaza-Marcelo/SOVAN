
@extends('backend.layouts.master')

@section('title')
@lang('messages.Modifier un congé payé') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Modifier un congé payé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.Modifier un congé payé')</span></li>
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
                    <h4 class="header-title">@lang('messages.Modifier un congé payé')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-take-conge-payes.update',$data->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="form-group">
                            <label for="employe_id">@lang('messages.Employé')</label>
                            <select class="form-control" name="employe_id" id="employe_id-dropdown">
                                <option disabled {{ !$data->employe_id ? 'selected' : '' }}>Merci de choisir</option>
                                @foreach($employes as $employe)
                                <option value="{{ $employe->id }}" {{ $data->employe_id == $employe->id ? 'selected' : '' }}>{{ $employe->firstname}}&nbsp;{{ $employe->lastname}}</option>
                                @endforeach
                            </select>
                        </div>
                        </div>
                    </div>
                    <p class="text-muted small">Si l'employé a déjà un autre congé en cours, la date de début doit être <strong>@lang('messages.strictement supérieure')</strong> @lang('messages.à la date de fin de son dernier congé.')</p>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_debut">@lang('messages.Date début')</label>
                                <input type="date" class="form-control" name="date_heure_debut" id="date_heure_debut" value="{{ $data->date_heure_debut ? \Carbon\Carbon::parse($data->date_heure_debut)->format('Y-m-d') : '' }}">
                                <small id="min_date_hint" class="form-text text-info d-none"></small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_fin">@lang('messages.Date fin')</label>
                                <input type="date" class="form-control" name="date_heure_fin" id="date_heure_fin" value="{{ $data->date_heure_fin ? \Carbon\Carbon::parse($data->date_heure_fin)->format('Y-m-d') : '' }}">
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
                                <label for="nbre_jours_conge_sollicite">@lang('messages.Nbre Jrs Congé Sollicité')</label>
                                <input type="number" class="form-control" name="nbre_jours_conge_sollicite" id="nbre_jours_conge_sollicite" min="3" max="15">
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
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
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
        $('#nbre_jours_conge_paye').html('<label>@lang('messages.Nbre Jrs Congé Annuel')</label><input type="number" name="nbre_jours_conge_paye" class="form-control" id="nbre_jours_conge_paye_value" readonly>');
        $('#nbre_jours_conge_pris').html('<label>@lang('messages.Nbre Jrs Congé Pris')</label><input type="number" name="nbre_jours_conge_pris" class="form-control" id="nbre_jours_conge_pris_value" readonly>');
        $('#nbre_jours_conge_restant').html('<label>@lang('messages.Nbre Jrs Congé Restant')</label><input type="number" name="nbre_jours_conge_restant" class="form-control" id="nbre_jours_conge_restant_value" readonly>');
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
        updateCongeDerivedFields();
    }

    var currentTakeCongePayeId = {{ $data->id ?? 0 }};

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
            data: { employe_id: idEmploye, exclude_id: currentTakeCongePayeId, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function (result) {
                if (result.min_date_debut) {
                    $('#date_heure_debut').attr('min', result.min_date_debut);
                    $('#min_date_hint').removeClass('d-none').text('Date minimale (après dernier autre congé) : ' + result.min_date_debut);
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

    $('#employe_id-dropdown').on('change', function () {
        const idEmploye = this.value;

        if (!idEmploye) {
            resetCongeDisplays();
            clearMinDateDebut();
            return;
        }

        fetchMinDateDebutForEmploye(idEmploye);

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
    });

    const MIN_CONGE_DAYS = 3;
    const MAX_CONGE_DAYS = 15;
    const msInDay = 1000 * 60 * 60 * 24;
    const twoDaysInMs = msInDay * 2;

    function formatDateToInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function setStartDateConstraints() {
        const today = new Date();
        const minStart = new Date(today.getTime() - twoDaysInMs);
        const maxStart = new Date(today.getTime() + twoDaysInMs);
        $('#date_heure_debut').attr('min', formatDateToInput(minStart));
        $('#date_heure_debut').attr('max', formatDateToInput(maxStart));
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

        const minEndDate = new Date(startDate.getTime() + twoDaysInMs);
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
        const clampedDiff = Math.max(MIN_CONGE_DAYS, Math.min(diff, MAX_CONGE_DAYS));
        $('#nbre_jours_conge_sollicite').val(clampedDiff);
        updateCongeDerivedFields();
    }

    function updateCongeDerivedFields() {
        const prisInput = $('#nbre_jours_conge_pris_value');
        const restantInput = $('#nbre_jours_conge_restant_value');

        if (!prisInput.length || !restantInput.length || !congeState) {
            return;
        }

        const sollicite = Number($('#nbre_jours_conge_sollicite').val());

        if (isNaN(sollicite) || sollicite <= 0) {
            prisInput.val(congeState.pris);
            restantInput.val(congeState.restant);
            return;
        }

        let updatedRestant = congeState.restant - sollicite;
        if (updatedRestant < 0) {
            updatedRestant = 0;
        }

        let updatedPris = congeState.paye - updatedRestant - sollicite;
        if (updatedPris < 0) {
            updatedPris = 0;
        }

        prisInput.val(updatedPris);
        restantInput.val(updatedRestant);
    }

    setStartDateConstraints();
    setEndDateConstraints();

    var initialEmployeId = $('#employe_id-dropdown').val();
    if (initialEmployeId) {
        fetchMinDateDebutForEmploye(initialEmployeId);
    }

    $('#date_heure_debut').on('change', function () {
        setEndDateConstraints();
        updateCongeSollicite();
    });

    $('#date_heure_fin').on('change', updateCongeSollicite);
    $('#nbre_jours_conge_sollicite').on('input', updateCongeDerivedFields);

</script>
@endsection
