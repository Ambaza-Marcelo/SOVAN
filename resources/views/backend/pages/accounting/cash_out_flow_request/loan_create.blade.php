
@extends('backend.layouts.master')

@section('title')
@lang('messages.Demande de sortie de fonds') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Demande de sortie de fonds')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.cash-out-flow-request.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Demande de sortie de fonds')</span></li>
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
        <!-- data table start -->
         <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">@lang('messages.Demande de sortie de fonds - Avance sur salaire')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.cash-out-flow-request.loan-store') }}" method="post" id="dynamic_form">
                        @csrf
                        @method('POST')
                    <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                    <div class="row">
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="datetime-local" class="form-control" id="date" name="date" value="{{ $loan->date ? \Carbon\Carbon::parse($loan->date)->format('Y-m-d\TH:i') : \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="asker">@lang('messages.asker')</label>
                                <input type="text" class="form-control" id="asker" name="asker" placeholder="@lang('messages.Enter Full name of Asker')" value="{{ $loan->employe ? $loan->employe->firstname . ' ' . $loan->employe->lastname : '' }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employe_search">@lang('messages.Employé')</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="employe_search" class="form-control" placeholder="@lang('messages.hr_type_to_search_employee')" autocomplete="off" value="{{ $loan->employe ? $loan->employe->firstname . ' ' . $loan->employe->lastname : '' }}" required>
                                    <input type="hidden" name="employe_id" id="employe_id" value="{{ $loan->employe_id }}">
                                    <div class="autocomplete-list" id="employe_search-list"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="cost_center_id">@lang('messages.Cost Center')</label>
                                <select class="form-control" name="cost_center_id" id="cost_center_id">
                                 <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                @foreach($cost_centers as $cost_center)
                                    <option value="{{$cost_center->id}}">{{$cost_center->name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="currency_id">@lang('messages.Currency')</label>
                                <select class="form-control" name="currency_id" id="currency_id" required>
                                @foreach($currencies as $currency)
                                    <option value="{{$currency->id}}" {{ strtoupper($currency->currency_name) == 'BIF' ? 'selected' : '' }}>{{$currency->currency_name}}</option>
                                @endforeach
                             </select>
                            </div>
                        </div>
                    </div>

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="">
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.Montant')</th>
                            </tr>
                            <tr class="">    
                                <td>
                                    <input type="text" class="form-control" value="{{ $loan->description ?: 'Avance sur salaire' }} - {{ number_format($loan->amount_loan, 0, ',', ' ') }} FBU" readonly />
                                </td> 
                                <td>
                                    <input type="number" name="amount_loan" class="form-control" min="0" value="{{ $loan->amount_loan }}" step="any" required readonly />
                                </td>  
                            </tr>
                        </table>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Enter Description')" required>{{ $loan->description ?: 'DEMANDE DE SORTIE DE FONDS - AVANCE SUR SALAIRE' }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.save')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript">
    
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
    function initializeAutocomplete(inputId, hiddenId, dataArray, askerFieldId) {
        var input = $('#' + inputId);
        var hidden = $('#' + hiddenId);
        var list = $('#' + inputId + '-list');
        var askerField = $('#' + askerFieldId);
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
                askerField.val('');
                return;
            }

            var filtered = dataArray.filter(function(item) {
                return item.name.toLowerCase().includes(searchText);
            });

            if (filtered.length > 0) {
                filtered.forEach(function(item) {
                    var div = $('<div></div>').text(item.name).attr('data-id', item.id).attr('data-name', item.name);
                    div.on('click', function() {
                        var selectedName = $(this).attr('data-name');
                        input.val(selectedName);
                        hidden.val($(this).attr('data-id'));
                        askerField.val(selectedName); // Auto-fill asker field
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

    // Initialize autocomplete for employees
    $(document).ready(function() {
        initializeAutocomplete('employe_search', 'employe_id', employees, 'asker');
    });

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

</script>
@endsection
