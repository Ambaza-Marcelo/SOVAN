
@extends('backend.layouts.master')

@section('title')
@lang('messages.stockin_create') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.stockin_create')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.material-stockins.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.stockin_create')</span></li>
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
                    <h4 class="header-title">@lang('messages.Nouveau Entré')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.material-stockins.storeFromSmall') }}" method="POST">
                        @csrf
                    <input type="hidden" name="store_type" value="sm">
                    <div class="row">
                        <div class="col-md-4" id="dynamicDiv">
                            <div class="form-group">
                             <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="handingover">@lang('messages.Remettant')</label>
                                <input type="text" class="form-control" id="handingover" name="handingover" placeholder="@lang('messages.Enter handingover')">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="origin">@lang('messages.Origin')</label>
                                <input type="text" class="form-control" id="origin" name="origin" placeholder="@lang('messages.Enter origin')">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="receptionist">@lang('messages.receptionist')</label>
                                    <input type="text" class="form-control" id="receptionist" name="receptionist" placeholder="@lang('messages.Enter receptionist')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dest_sm_store_id">@lang('messages.Stock Destination')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="dest_sm_store_id" id="dest_sm_store_id" required>
                                    <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                    @foreach($destination_stores as $destination_store)
                                        <option value="{{$destination_store->id}}">{{$destination_store->name}}</option>
                                    @endforeach
                                </select>
                                </div>
                            </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_movement_type">@lang('messages.Type Entrée')</label>
                                    <select class="form-control" name="item_movement_type" id="item_movement_type" required>
                                    <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        <option value="EN">@lang('messages.Entrée Normale')</option>
                                        <option value="ER">@lang('messages.Entrée Retour Marchandises')</option>
                                        @if (Auth::guard('admin')->user()->can('material_stockin.reject'))
                                        <option value="EAJ">@lang('messages.Entrée Ajustement')</option>
                                        @endif
                                        <option value="EAU">@lang('messages.Entrée Autre')</option>
                                </select>
                                </div>
                            </div>
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.purchase_price')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            <tr>  
                                <td>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" class="form-control material_search" placeholder="@lang('messages.Tapez pour rechercher un article...')" autocomplete="off" required>
                                        <input type="hidden" name="material_id[]" class="material_id">
                                        <div class="autocomplete-list material_search-list"></div>
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" placeholder="@lang('messages.Enter quantity')" class="form-control" step="any" min="0" /></td>  
                                <td><input type="number" name="purchase_price[]" placeholder="@lang('messages.Enter purchase price')" class="form-control" step="any" min="0" /></td>    
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>  
                            </tr>  
                        </table> 
                        <div class="col-lg-12">
                            <label for="description"> @lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Entrer description')">
                                MOI {{ Auth::guard('admin')->user()->name }},J'ACCUSE L'ENTREE DE CES ARTICLES
                            </textarea>
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
    var i18n = {
        searchItem: @json(__('messages.Tapez pour rechercher un article...')),
        quantity: @json(__('messages.Enter quantity')),
        costPrice: @json(__('messages.Enter cost price')),
        purchasePrice: @json(__('messages.Enter purchase price')),
        unitPrice: @json(__('messages.Enter Unit price')),
        delete: @json(__('messages.delete'))
    };
</script>
    <script type="text/javascript">
    
    // Materials data for autocomplete
    var materials = [
        @foreach($materials as $material)
        {
            id: {{ $material->id }},
            name: {!! json_encode($material->name . '/' . number_format($material->cump,0,',',' ') . '/' . $material->materialMeasurement->purchase_unit) !!},
            cump: {{ $material->cump ?? 0 }}
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
                    var div = $('<div></div>').text(item.name).attr('data-id', item.id).attr('data-name', item.name).attr('data-cump', item.cump);
                    div.on('click', function() {
                        input.val($(this).attr('data-name'));
                        hidden.val($(this).attr('data-id'));
                        // Auto-fill purchase_price with CUMP value
                        var row = input.closest('tr');
                        var purchasePriceInput = row.find('input[name="purchase_price[]"]');
                        purchasePriceInput.val($(this).attr('data-cump'));
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
                    var selectedItem = $(items[selectedIndex]);
                    var materialId = selectedItem.attr('data-id');
                    var materialName = selectedItem.attr('data-name');
                    var materialCump = selectedItem.attr('data-cump');
                    
                    input.val(materialName);
                    hidden.val(materialId);
                    // Auto-fill purchase_price with CUMP value
                    var row = input.closest('tr');
                    var purchasePriceInput = row.find('input[name="purchase_price[]"]');
                    purchasePriceInput.val(materialCump);
                    list.hide();
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
                            "<input type='text' class='form-control material_search' placeholder='" + i18n.searchItem + "' autocomplete='off' required>"+
                            "<input type='hidden' name='material_id[]' class='material_id'>"+
                            "<div class='autocomplete-list material_search-list'></div>"+
                         "</div>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' placeholder='" + i18n.quantity + "' class='form-control' step='any' min='0' />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' name='purchase_price[]' placeholder='" + i18n.purchasePrice + "' class='form-control' step='any' min='0' />"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>"+
                        "</td>"+
                    "</tr>";
   
        var newRow = $(markup);
        $("#dynamicTable").append(newRow);
        
        // Initialize autocomplete for the new row
        initializeMaterialAutocomplete(newRow.find('.autocomplete-wrapper'));
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
