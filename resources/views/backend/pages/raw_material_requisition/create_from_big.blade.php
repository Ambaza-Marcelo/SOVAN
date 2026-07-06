
@extends('backend.layouts.master')

@section('title')
@lang('messages.Demande de Requisition des Articles') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.Demande de Requisition des Articles')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-requisitions.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.Demande de Requisition des Articles')</span></li>
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
                    <h4 class="header-title">@lang('messages.Demande de Requisition des Articles')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.raw-material-requisitions.store') }}" method="post" id="dynamic_form">
                        @csrf
                    <input type="hidden" name="type_store" value="bg">
                    <div class="row">
                        <div class="col-sm-6" id="dynamicDiv">
                            <div class="form-group">
                                <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date">
                            </div>
                        </div>
                    </div>

                         <table class="table table-bordered" id="dynamicTable">  
                            <tr class="">
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            <tr class="">  
                                <td>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" class="form-control food_search" placeholder="{{ __('messages.Tapez pour rechercher un article...') }}" autocomplete="off" required>
                                        <input type="hidden" name="r_material_id[]" class="r_material_id">
                                        <div class="autocomplete-list food_search-list"></div>
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity_requisitioned[]" placeholder="{{ __('messages.Enter Quantity') }}" class="form-control" min="0" step="any" /></td>
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>     
                            </tr>
                        </table> 
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="{{ __('messages.Enter Description') }}">{{ old('description', __('messages.raw_material_requisition_default_description')) }}</textarea>
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
    var i18n = {
        searchArticle: @json(__('messages.Tapez pour rechercher un article...')),
        enterQuantity: @json(__('messages.Enter Quantity')),
        delete: @json(__('messages.delete'))
    };
    
    // Foods data for autocomplete
    var foods = [
        @foreach($raw_materials as $raw_material)
        {
            id: {{ $raw_material->id }},
            name: {!! json_encode($raw_material->name . '/' . $raw_material->rawMaterialMeasurement->purchase_unit) !!}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    // Function to initialize food autocomplete for a specific row
    function initializeFoodAutocomplete(wrapper) {
        var input = wrapper.find('.food_search');
        var hidden = wrapper.find('.r_material_id');
        var list = wrapper.find('.food_search-list');
        var selectedIndex = -1;

        // Remove any existing event handlers
        input.off('input.food keydown.food');

        input.on('input.food', function() {
            var searchText = $(this).val().toLowerCase();
            list.empty();
            selectedIndex = -1;

            if (searchText === '') {
                list.hide();
                hidden.val('');
                return;
            }

            var filtered = foods.filter(function(item) {
                return item.name.toLowerCase().includes(searchText);
            });

            if (filtered.length > 0) {
                filtered.forEach(function(item) {
                    var div = $('<div></div>').text(item.name).attr('data-id', item.id).attr('data-name', item.name);
                    div.on('click', function() {
                        input.val($(this).attr('data-name'));
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
        input.on('keydown.food', function(e) {
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

    // Initialize autocomplete for existing food rows
    $(document).ready(function() {
        $('.autocomplete-wrapper').each(function() {
            if ($(this).find('.food_search').length) {
                initializeFoodAutocomplete($(this));
            }
        });
    });
    
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<div class='autocomplete-wrapper'>"+
                            "<input type='text' class='form-control food_search' placeholder=\"" + i18n.searchArticle + "\" autocomplete='off' required>"+
                            "<input type='hidden' name='r_material_id[]' class='r_material_id'>"+
                            "<div class='autocomplete-list food_search-list'></div>"+
                         "</div>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity_requisitioned[]' placeholder=\"" + i18n.enterQuantity + "\" class='form-control' min='0' step='any'/>"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                        "</td>"+
                    "</tr>";
   
        var newRow = $(markup);
        $("#dynamicTable").append(newRow);
        
        // Initialize autocomplete for the new row
        initializeFoodAutocomplete(newRow.find('.autocomplete-wrapper'));
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
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
