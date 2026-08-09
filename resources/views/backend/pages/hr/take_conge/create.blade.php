
@extends('backend.layouts.master')

@section('title')
@lang('Prendre un congé') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('Prendre un congé')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><span>@lang('Prendre congé')</span></li>
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
                    <h4 class="header-title">Prendre un congé</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.hr-take-conges.store') }}" method="POST">
                        @csrf
                    <input type="hidden" name="company_id" value="{{ $company_id }}">
                    <div class="row">
                        <div class="col-md-6">
                                    <div class="form-group has-feedback">
                                        <label for="type_employe">Type Personnel<span class="text-danger"></span></label>
                                        <select id="type_employe" class="form-control" name="type_employe">
                                            <option disabled selected>Merci de choisir</option>
                                            <option value="1">Employé</option>
                                            <option value="2">Stagiaire</option>
                                        </select>
                                    </div>
                        </div>
                        <div class="col-md-6" id="dynamicStagiaire">
     
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6" id="dynamicEmploye">

                        </div>
                        <div class="col-sm-6">
                        <div class="form-group">
                            <label for="type_conge_id">@lang('Type Congé')</label>
                            <select class="form-control" name="type_conge_id">
                                <option disabled selected>Merci de choisir</option>
                                @foreach($type_conges as $type_conge)
                                <option value="{{ $type_conge->id }}">{{ $type_conge->libelle}}</option>
                                @endforeach
                            </select>
                        </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_debut">@lang('Date de départ')</label>
                                <input type="datetime-local" class="form-control" id="date_heure_debut" name="date_heure_debut" min="{{ date('Y') }}-{{date('m')}}/{{ date('d')}}T00:00">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="date_heure_fin">@lang('Date de retour')</label>
                                <input type="datetime-local" class="form-control" id="date_heure_fin" name="date_heure_fin">
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
    <script type="text/javascript">
    
    // Function to format date for datetime-local input
    function formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    // Function to add hours to a date
    function addHours(date, hours) {
        const newDate = new Date(date);
        newDate.setHours(newDate.getHours() + hours);
        return newDate;
    }

    // Auto-complete date_heure_debut with current date and time
    $(document).ready(function() {
        const now = new Date();
        const currentDateTime = formatDateTimeLocal(now);
        $('#date_heure_debut').val(currentDateTime);

        // Auto-complete date_heure_fin with date_heure_debut + 2 hours
        const endDate = addHours(now, 2);
        const endDateTime = formatDateTimeLocal(endDate);
        $('#date_heure_fin').val(endDateTime);
        $('#date_heure_fin').attr('min', endDateTime);
    });

    // Update date_heure_fin when date_heure_debut changes
    $('#date_heure_debut').on('change', function() {
        const startDateValue = $(this).val();
        if (startDateValue) {
            const startDate = new Date(startDateValue);
            const endDate = addHours(startDate, 2);
            const endDateTime = formatDateTimeLocal(endDate);
            
            // Get current end date value
            const currentEndDate = $('#date_heure_fin').val();
            const currentEndDateObj = currentEndDate ? new Date(currentEndDate) : null;
            
            // Only update if current end date is before the minimum or doesn't exist
            if (!currentEndDateObj || currentEndDateObj < endDate) {
                $('#date_heure_fin').val(endDateTime);
            }
            
            // Update min attribute
            $('#date_heure_fin').attr('min', endDateTime);
        }
    });

    // Employees data for autocomplete
    var employees = [
        @foreach($employes as $employe)
        {
            id: {{ $employe->id }},
            name: {!! json_encode($employe->firstname . ' ' . $employe->lastname) !!}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    // Stagiaires data for autocomplete
    var stagiaires = [
        @foreach($stagiaires as $stagiaire)
        {
            id: {{ $stagiaire->id }},
            name: {!! json_encode($stagiaire->firstname . ' ' . $stagiaire->lastname) !!}
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
    
    $('#type_employe').change(function () { 
        $("#dynamicEmploye").empty();
        $("#dynamicStagiaire").empty();
        
        if ($(this).val() === '1'){
            var employe = "<div class='form-group'>"+
                                "<label for='employe_search'>Employé</label>"+
                                "<div class='autocomplete-wrapper'>"+
                                    "<input type='text' id='employe_search' class='form-control' placeholder='Tapez pour rechercher un employé...' autocomplete='off'>"+
                                    "<input type='hidden' id='employe_id' name='employe_id'>"+
                                    "<div class='autocomplete-list' id='employe_search-list'></div>"+
                                "</div>"+
                            "</div>";
            
            $("#dynamicEmploye").append(employe);
            $("#dynamicStagiaire").hide();
            
            // Initialize autocomplete for employees
            setTimeout(function() {
                initializeAutocomplete('employe_search', 'employe_id', employees);
            }, 100);
        }
        if ($(this).val() === '2'){
            var stagiaire = "<div class='form-group'>"+
                                "<label for='stagiaire_search'>Stagiaire</label>"+
                                "<div class='autocomplete-wrapper'>"+
                                    "<input type='text' id='stagiaire_search' class='form-control' placeholder='Tapez pour rechercher un stagiaire...' autocomplete='off'>"+
                                    "<input type='hidden' id='stagiaire_id' name='stagiaire_id'>"+
                                    "<div class='autocomplete-list' id='stagiaire_search-list'></div>"+
                                "</div>"+
                            "</div>";
            
            $("#dynamicStagiaire").append(stagiaire);
            $("#dynamicEmploye").hide();
            
            // Initialize autocomplete for stagiaires
            setTimeout(function() {
                initializeAutocomplete('stagiaire_search', 'stagiaire_id', stagiaires);
            }, 100);
        }
    })
    .trigger( "change" );


</script>
@endsection
