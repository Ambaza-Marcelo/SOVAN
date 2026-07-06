
@extends('backend.layouts.master')

@section('title')
@lang('messages.Prendre un congé payé') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .autocomplete-wrapper {
        position: relative;
    }
    .autocomplete-list {
        position: absolute;
        border: 1px solid #d4d4d4;
        border-top: none;
        z-index: 99;
        max-height: 200px;
        overflow-y: auto;
        background-color: #fff;
        width: 100%;
        display: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: -1px;
    }
    .autocomplete-list div {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #d4d4d4;
    }
    .autocomplete-list div:hover {
        background-color: #e9e9e9;
    }
    .autocomplete-list div:last-child {
        border-bottom: none;
    }
    .autocomplete-active {
        background-color: #4CAF50 !important;
        color: #ffffff;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.Prendre un congé payé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('messages.Prendre un congé payé')</span></li>
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
                    <h4 class="header-title">@lang('messages.Prendre un congé payé')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-take-conge-payes.store') }}" method="POST">
                        @csrf
                    <input type="hidden" name="company_id" value="{{ $company_id }}">
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="form-group">
                            <label for="employe_search">@lang('messages.Employé')</label>
                            <div class="autocomplete-wrapper">
                                <input type="text" id="employe_search" class="form-control" placeholder="{{ __('messages.hr_type_to_search_employee') }}" autocomplete="off">
                                <input type="hidden" name="employe_id" id="employe_id">
                                <div class="autocomplete-list" id="employe_search-list"></div>
                            </div>
                        </div>
                        </div>
                    </div>
                    <p class="text-muted small">Si l'employé a déjà un congé en cours, la date de début du nouveau congé doit être <strong>@lang('messages.strictement supérieure')</strong> à la date de fin de son dernier congé (date suivante possible).</p>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_debut">@lang('messages.Date début')</label>
                                <input type="date" class="form-control" name="date_heure_debut" id="date_heure_debut">
                                <small id="min_date_hint" class="form-text text-info d-none"></small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_fin">@lang('messages.Date fin')</label>
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
                                <label for="nbre_jours_conge_sollicite">@lang('messages.Nbre Jrs Congé Sollicité')</label>
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

    // Employees data for autocomplete
    var employees = [
        @foreach($employes as $employe)
        {
            id: {{ $employe->id }},
            name: {!! json_encode($employe->firstname . ' ' . $employe->lastname) !!}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    // Function to initialize autocomplete
    function initializeAutocomplete(inputId, hiddenId, dataArray) {
        var input = $('#' + inputId);
        var hidden = $('#' + hiddenId);
        var list = $('#' + inputId + '-list');
        var selectedIndex = -1;

        // Remove any existing event handlers to avoid duplicates
        input.off('input.autocomplete keydown.autocomplete');

        input.on('input.autocomplete', function() {
            var searchText = $(this).val().toLowerCase();
            list.empty();
            selectedIndex = -1;

            if (searchText === '') {
                list.hide();
                hidden.val('');
                fetchEmployeData('');
                clearMinDateDebut();
                return;
            }

            var filtered = dataArray.filter(function(item) {
                return item.name.toLowerCase().includes(searchText);
            });

            if (filtered.length > 0) {
                filtered.forEach(function(item) {
                    var div = $('<div></div>').text(item.name).attr('data-id', item.id);
                    div.on('click', function() {
                        input.val($(this).text());
                        hidden.val($(this).attr('data-id'));
                        list.hide();
                        var idEmploye = $(this).attr('data-id');
                        fetchEmployeData(idEmploye);
                        fetchMinDateDebutForEmploye(idEmploye);
                    });
                    list.append(div);
                });
                list.show();
            } else {
                list.hide();
            }
        });

        // Keyboard navigation
        input.on('keydown.autocomplete', function(e) {
            var items = list.find('div');
            
            if (e.keyCode === 40) { // Down arrow
                e.preventDefault();
                selectedIndex = (selectedIndex < items.length - 1) ? selectedIndex + 1 : selectedIndex;
                items.removeClass('autocomplete-active');
                if (selectedIndex >= 0) {
                    $(items[selectedIndex]).addClass('autocomplete-active');
                }
            } else if (e.keyCode === 38) { // Up arrow
                e.preventDefault();
                selectedIndex = (selectedIndex > 0) ? selectedIndex - 1 : -1;
                items.removeClass('autocomplete-active');
                if (selectedIndex >= 0) {
                    $(items[selectedIndex]).addClass('autocomplete-active');
                }
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                if (selectedIndex >= 0 && items.length > 0) {
                    $(items[selectedIndex]).click();
                }
            } else if (e.keyCode === 27) { // Escape
                list.hide();
            }
        });
    }
    
    // Hide autocomplete lists when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.autocomplete-wrapper').length) {
            $('.autocomplete-list').hide();
        }
    });

    // Initialize autocomplete for employees
    $(document).ready(function() {
        initializeAutocomplete('employe_search', 'employe_id', employees);
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

    $('#date_heure_debut').on('change', function () {
        setEndDateConstraints();
        updateCongeSollicite();
    });

    $('#date_heure_fin').on('change', updateCongeSollicite);
    $('#nbre_jours_conge_sollicite').on('input', updateEndDateFromSollicite);

</script>
@endsection
