
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
                    <li><a href="{{ route('admin.material-requisitions.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.edit')</span></li>
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
                    <h4 class="header-title">Edit Requisition: {{ $requisition->requisition_no }}</h4>
                    @include('backend.layouts.partials.messages')

                    <form action="{{ route('admin.material-requisitions.update', $requisition->requisition_no) }}" method="post" id="dynamic_form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-sm-6" id="dynamicDiv">
                                <div class="form-group">
                                    <label for="date">@lang('messages.date')</label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($requisition->date)->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="type_store">@lang('messages.Type Store')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="type_store" id="type_store" required>
                                        <option value="md" {{ $requisition->type_store == 'md' ? 'selected' : '' }}>@lang('messages.requisition_type_md_label')</option>
                                        <option value="bg" {{ $requisition->type_store == 'bg' ? 'selected' : '' }}>@lang('messages.requisition_type_bg_label')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="store_selection_row">
                            @if($requisition->type_store == 'md')
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="origin_store_id">@lang('messages.Stock Origine')</label>
                                    <select class="form-control" name="origin_store_id" id="origin_store_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($origin_stores as $origin_store)
                                            <option value="{{$origin_store->id}}" {{ old('origin_store_id') == $origin_store->id ? 'selected' : '' }}>{{$origin_store->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="destination_store_id">@lang('messages.Stock Destination')</label>
                                    <select class="form-control" name="destination_store_id" id="destination_store_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($destination_stores as $destination_store)
                                            <option value="{{$destination_store->id}}" {{ old('destination_store_id') == $destination_store->id ? 'selected' : '' }}>{{$destination_store->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @else
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="origin_extra_store_id">@lang('messages.Stock Origine')</label>
                                    <select class="form-control" name="origin_extra_store_id" id="origin_extra_store_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($origin_extra_stores as $origin_store)
                                            <option value="{{$origin_store->id}}" {{ old('origin_extra_store_id') == $origin_store->id ? 'selected' : '' }}>{{$origin_store->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="dest_bg_store_id">@lang('messages.Stock Destination')</label>
                                    <select class="form-control" name="dest_bg_store_id" id="dest_bg_store_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($destination_bg_stores as $destination_store)
                                            <option value="{{$destination_store->id}}" {{ old('dest_bg_store_id') == $destination_store->id ? 'selected' : '' }}>{{$destination_store->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>

                        <table class="table table-bordered" id="dynamicTable">  
                            <thead>
                                <tr>
                                    <th>@lang('messages.item')</th>
                                    <th>@lang('messages.quantity')</th>
                                    <th>@lang('messages.Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisitionDetails as $detail)
                                <tr>  
                                    <td>
                                        <div class="autocomplete-wrapper">
                                            <input type="text" class="form-control material_search" placeholder="{{ __('messages.Tapez pour rechercher un article...') }}" autocomplete="off" value="{{ $detail->material->name }}/{{ number_format($detail->material->cump,0,',',' ') }}/{{ $detail->material->materialMeasurement->purchase_unit ?? __('messages.not_available_short') }}" required>
                                            <input type="hidden" name="material_id[]" class="material_id" value="{{ $detail->material_id }}">
                                            <div class="autocomplete-list material_search-list"></div>
                                        </div>
                                    </td>  
                                    <td><input type="number" name="quantity_requisitioned[]" placeholder="{{ __('messages.Enter Quantity') }}" class="form-control" min="0" value="{{ $detail->quantity_requisitioned }}" required/></td>
                                    <td>
                                        <button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button>
                                    </td>     
                                </tr>
                                @endforeach
                            </tbody>
                        </table> 
                        <button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button>
                        <div class="col-lg-12">
                            <label for="description">@lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="{{ __('messages.Enter Description') }}">{{ old('description', $requisition->description) }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary" id="save">@lang('messages.update')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
    var i18n = {
        searchArticle: @json(__('messages.Tapez pour rechercher un article...')),
        enterQuantity: @json(__('messages.Enter Quantity')),
        delete: @json(__('messages.delete')),
        pleaseChoose: @json(__('messages.Merci de choisir'))
    };
    
    // Materials data for autocomplete
    var materials = [
        @foreach($materials as $material)
        {
            id: {{ $material->id }},
            name: {!! json_encode($material->name . '/' . number_format($material->cump,0,',',' ') . '/' . ($material->materialMeasurement->purchase_unit ?? 'N/A')) !!}
        }@if(!$loop->last),@endif
        @endforeach
    ];

    // Function to initialize material autocomplete for a specific row
    function initializeMaterialAutocomplete(wrapper) {
        var input = wrapper.find('.material_search');
        var hidden = wrapper.find('.material_id');
        var list = wrapper.find('.material_search-list');
        var selectedIndex = -1;

        // Remove any existing event handlers
        input.off('input.material keydown.material');

        input.on('input.material', function() {
            var searchText = $(this).val().toLowerCase();
            list.empty();
            selectedIndex = -1;

            if (searchText === '') {
                list.hide();
                hidden.val('');
                return;
            }

            var filtered = materials.filter(function(item) {
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
        input.on('keydown.material', function(e) {
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

    // Store data for dynamic store selection
    var originStores = [
        @foreach($origin_stores as $store)
        {id: {{ $store->id }}, name: {!! json_encode($store->name) !!}}@if(!$loop->last),@endif
        @endforeach
    ];
    var destinationStores = [
        @foreach($destination_stores as $store)
        {id: {{ $store->id }}, name: {!! json_encode($store->name) !!}}@if(!$loop->last),@endif
        @endforeach
    ];
    var originExtraStores = [
        @foreach($origin_extra_stores as $store)
        {id: {{ $store->id }}, name: {!! json_encode($store->name) !!}}@if(!$loop->last),@endif
        @endforeach
    ];
    var destinationBgStores = [
        @foreach($destination_bg_stores as $store)
        {id: {{ $store->id }}, name: {!! json_encode($store->name) !!}}@if(!$loop->last),@endif
        @endforeach
    ];

    // Initialize autocomplete for existing material rows
    $(document).ready(function() {
        $('.autocomplete-wrapper').each(function() {
            if ($(this).find('.material_search').length) {
                initializeMaterialAutocomplete($(this));
            }
        });
    });
    
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<div class='autocomplete-wrapper'>"+
                            "<input type='text' class='form-control material_search' placeholder=\"" + i18n.searchArticle + "\" autocomplete='off' required>"+
                            "<input type='hidden' name='material_id[]' class='material_id'>"+
                            "<div class='autocomplete-list material_search-list'></div>"+
                         "</div>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity_requisitioned[]' placeholder=\"" + i18n.enterQuantity + "\" class='form-control' min='0'/>"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>" + i18n.delete + "</button>"+
                        "</td>"+
                    "</tr>";
   
        var newRow = $(markup);
        $("#dynamicTable tbody").append(newRow);
        
        // Initialize autocomplete for the new row
        initializeMaterialAutocomplete(newRow.find('.autocomplete-wrapper'));
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
    }); 

    // Handle type_store change
    $('#type_store').change(function() {
        var typeStore = $(this).val();
        var storeSelectionRow = $('#store_selection_row');
        
        if (typeStore === 'md') {
            // STOCK INTERMEDIAIRE VERS PETIT STOCK
            var originHtml = '<div class="col-sm-6"><div class="form-group"><label for="origin_store_id">@lang('messages.Stock Origine')</label><select class="form-control" name="origin_store_id" id="origin_store_id"><option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>';
            originStores.forEach(function(store) {
                originHtml += '<option value="' + store.id + '">' + store.name + '</option>';
            });
            originHtml += '</select></div></div>';
            
            var destinationHtml = '<div class="col-sm-6"><div class="form-group"><label for="destination_store_id">@lang('messages.Stock Destination')</label><select class="form-control" name="destination_store_id" id="destination_store_id"><option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>';
            destinationStores.forEach(function(store) {
                destinationHtml += '<option value="' + store.id + '">' + store.name + '</option>';
            });
            destinationHtml += '</select></div></div>';
            
            storeSelectionRow.html(originHtml + destinationHtml);
        } else if (typeStore === 'bg') {
            // GRAND STOCK VERS STOCK INTERMEDIAIRE
            var originHtml = '<div class="col-sm-6"><div class="form-group"><label for="origin_extra_store_id">@lang('messages.Stock Origine')</label><select class="form-control" name="origin_extra_store_id" id="origin_extra_store_id"><option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>';
            originExtraStores.forEach(function(store) {
                originHtml += '<option value="' + store.id + '">' + store.name + '</option>';
            });
            originHtml += '</select></div></div>';
            
            var destinationHtml = '<div class="col-sm-6"><div class="form-group"><label for="dest_bg_store_id">@lang('messages.Stock Destination')</label><select class="form-control" name="dest_bg_store_id" id="dest_bg_store_id"><option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>';
            destinationBgStores.forEach(function(store) {
                destinationHtml += '<option value="' + store.id + '">' + store.name + '</option>';
            });
            destinationHtml += '</select></div></div>';
            
            storeSelectionRow.html(originHtml + destinationHtml);
        }
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

