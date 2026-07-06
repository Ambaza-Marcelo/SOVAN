
@extends('backend.layouts.master')

@section('title')
@lang('messages.stockout_edit') - @lang('messages.admin_panel')
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
                <h4 class="page-title pull-left">@lang('messages.stockout_edit')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-stockouts.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.stockout_edit')</span></li>
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
                    <h4 class="header-title">@lang(\'messages.Modifier Sortie\') - {{ $stockout->stockout_no }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    @if($stockout->status == 4)
                    <div class="alert alert-warning">
                        <strong>@lang('messages.Attention!')</strong> Ce stockout a été approuvé (status = 4). La modification n'est pas autorisée.
                    </div>
                    @endif
                    
                    <form action="{{ route('admin.raw-material-stockouts.update', $stockout->stockout_no) }}" method="POST">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-md-6" id="dynamicDiv">
                            <div class="form-group">
                             <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($stockout->date)->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="asker">@lang('messages.Demandeur')</label>
                                <input type="text" class="form-control" id="asker" name="asker" placeholder="@lang('messages.Enter asker')" value="{{ $stockout->asker }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="store_type">@lang('messages.Store Type')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="store_type" id="store_type" required>
                                        <option disabled="disabled">@lang('messages.Merci de choisir')</option>
                                        @if (Auth::guard('admin')->user()->can('raw_material_extra_big_inventory.view'))
                                        <option value="0" {{ $stockout->store_type == '0' ? 'selected' : '' }}>Food Extra Big Store</option>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_big_inventory.view'))
                                        <option value="1" {{ $stockout->store_type == '1' ? 'selected' : '' }}>@lang('messages.Stock intermediaire des matieres premieres')</option>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_small_inventory.view'))
                                        <option value="2" {{ $stockout->store_type == '2' ? 'selected' : '' }}>Petit Stock des matières premières</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                            <div class="form-group">
                                <label for="destination">@lang('messages.Destination')</label>
                                <input type="text" class="form-control" id="destination" name="destination" placeholder="@lang('messages.Enter destination')" value="{{ $stockout->destination }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                                <div class="form-group">
                                    <label for="item_movement_type">@lang('messages.Type de Sortie')</label>
                                    <select class="form-control" name="item_movement_type" id="item_movement_type" required>
                                    <option disabled="disabled">@lang('messages.Merci de choisir')</option>
                                        <option value="SN" {{ $stockout->item_movement_type == 'SN' ? 'selected' : '' }}>@lang('messages.Sortie Normale')</option>
                                        @if (Auth::guard('admin')->user()->can('raw_material_stockout.edit'))
                                        <option value="SP" {{ $stockout->item_movement_type == 'SP' ? 'selected' : '' }}>@lang('messages.Sortie Perte')</option>
                                        <option value="SV" {{ $stockout->item_movement_type == 'SV' ? 'selected' : '' }}>@lang('messages.Sortie Vol')</option>
                                        <option value="SD" {{ $stockout->item_movement_type == 'SD' ? 'selected' : '' }}>@lang('messages.Sortie Désuétude')</option>
                                        <option value="SC" {{ $stockout->item_movement_type == 'SC' ? 'selected' : '' }}>@lang('messages.Sortie Casse')</option>
                                        <option value="SAJ" {{ $stockout->item_movement_type == 'SAJ' ? 'selected' : '' }}>@lang('messages.Sortie Ajustement')</option>
                                        @endif
                                        <option value="ST" {{ $stockout->item_movement_type == 'ST' ? 'selected' : '' }}>@lang('messages.Sortie Transfert')</option>
                                        <option value="SAU" {{ $stockout->item_movement_type == 'SAU' ? 'selected' : '' }}>@lang('messages.Sortie Autre')</option>
                                </select>
                                </div>
                            </div>
                    </div>
                        <div class="row">
                            <div class="col-md-6" id="dynamic_big_store">
                                
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6" id="dynamic_small_store">
                                
                            </div>
                        </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($stockoutDetails as $detail)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" class="form-control food_search" placeholder="@lang('messages.Tapez pour rechercher un article...')" autocomplete="off" value="{{ $detail->rawMaterial ? $detail->rawMaterial->name : '' }}" required>
                                        <input type="hidden" name="r_material_id[]" class="r_material_id" value="{{ $detail->r_material_id }}">
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" step="any" placeholder="@lang('messages.Enter quantity')" class="form-control" min="0" value="{{ $detail->quantity ?? 0 }}" required /></td>    
                                <td>
                                    @if($loop->first)
                                        <button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button>
                                    @else
                                        <button type="button" class="btn btn-danger remove-tr">@lang('messages.delete')</button>
                                    @endif
                                </td>  
                            </tr>
                            @endforeach
                        </table> 
                        <div class="col-lg-12">
                            <label for="description"> @lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Entrer description')">{{ $stockout->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                            <a href="{{ route('admin.raw-material-stockouts.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
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
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">

    function initFoodAutocomplete($wrap) {
        attachAjaxAutocomplete({
            $input: $wrap.find('.food_search'),
            $hidden: $wrap.find('.r_material_id'),
            url: '{{ route("admin.autocomplete.raw-materials") }}',
            minChars: 1
        });
    }

    $(document).ready(function() {
        $('.ajax-ac-wrap').each(function() {
            if ($(this).find('.food_search').length) {
                initFoodAutocomplete($(this));
            }
        });
    });
    
    var i = 0;
       
    $("#add").click(function(){
   
        ++i;

         var markup = "<tr>"+
                      "<td>"+
                         "<div class='ajax-ac-wrap'>"+
                            "<input type='text' class='form-control food_search' placeholder='" + i18n.searchItem + "' autocomplete='off' required>"+
                            "<input type='hidden' name='r_material_id[]' class='r_material_id'>"+
                         "</div>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' step='any' min='0' placeholder='" + i18n.quantity + "' class='form-control' required />"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>"+
                        "</td>"+
                    "</tr>";
   
        var newRow = $(markup);
        $("#dynamicTable").append(newRow);
        
        initFoodAutocomplete(newRow.find('.ajax-ac-wrap'));
    });
   
    $(document).on('click', '.remove-tr', function(){  
         $(this).parents('tr').remove();
    }); 


    $('#store_type').change(function () { 
        $("#dynamic_big_store").empty();
        $("#dynamic_small_store").empty();
        
    if ($(this).val() === '0'){
        var extra_big_store = "<div class='form-group'>"+
                            "<label for='origin_extra_store_id'>@lang('messages.Food Extra Big Store')<strong style='color: red;'>*</strong></label>"+
                            "<select name='origin_extra_store_id' class='form-control' required>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($raw_material_extra_big_stores as $raw_material_big_store)"+
                                "<option value='{{$raw_material_big_store->id}}' {{ $stockout->origin_extra_store_id == $raw_material_big_store->id ? 'selected' : '' }}>{{ $raw_material_big_store->code}}&nbsp;{{ $raw_material_big_store->name}}"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_big_store").append(extra_big_store);
        $("#dynamic_small_store").hide();
    }
    if ($(this).val() === '1'){
        var big_store = "<div class='form-group'>"+
                            "<label for='origin_bg_store_id'>@lang('messages.Stock intermediaire des matieres premieres')<strong style='color: red;'>*</strong></label>"+
                            "<select name='origin_bg_store_id' class='form-control' required>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($raw_material_big_stores as $raw_material_big_store)"+
                                "<option value='{{$raw_material_big_store->id}}' {{ $stockout->origin_bg_store_id == $raw_material_big_store->id ? 'selected' : '' }}>{{ $raw_material_big_store->code}}&nbsp;{{ $raw_material_big_store->name}}"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_big_store").append(big_store);
        $("#dynamic_small_store").hide();
    }
    if ($(this).val() === '2'){
        var small_store = "<div class='form-group'>"+
                            "<label for='origin_sm_store_id'>@lang('messages.Petit Stock des matières premières')<strong style='color: red;'>*</strong></label>"+
                            "<select name='origin_sm_store_id' class='form-control' required>"+
                                "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                "@foreach($raw_material_small_stores as $raw_material_small_store)"+
                                "<option value='{{$raw_material_small_store->id}}' {{ $stockout->origin_sm_store_id == $raw_material_small_store->id ? 'selected' : '' }}>{{ $raw_material_small_store->code }}&nbsp;{{ $raw_material_small_store->name}}</option>"+
                                "@endforeach"
                            +
                        "</div>";
        
        $("#dynamic_small_store").append(small_store);
        $("#dynamic_big_store").hide();
    }

    })
    .trigger( "change" );

    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () {
        null
    };

</script>
@endsection

