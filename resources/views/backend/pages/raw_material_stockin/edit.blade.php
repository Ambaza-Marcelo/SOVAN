
@extends('backend.layouts.master')

@section('title')
@lang('messages.stockin_edit') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .form-check-label {
        text-transform: capitalize;
    }
    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffc107;
        color: #856404;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
</style>
@endsection


@section('admin-content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('messages.stockin_edit')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.raw-material-stockins.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.stockin_edit')</span></li>
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
                    <h4 class="header-title">@lang(\'messages.Modifier Entré\') - {{ $stockin->stockin_no ?? '' }}</h4>
                    @include('backend.layouts.partials.messages')
                    
                    @if($stockin->status == 4)
                    <div class="alert-warning">
                        <strong>@lang('messages.Attention!')</strong> Ce stockin a été approuvé (status = 4) et ne peut pas être modifié.
                    </div>
                    @endif
                    
                    <form action="{{ route('admin.raw-material-stockins.update', $stockin->stockin_no) }}" method="POST">
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-md-4" id="dynamicDiv">
                            <div class="form-group">
                             <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ \Carbon\Carbon::parse($stockin->date)->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="handingover">@lang('messages.Remettant')</label>
                                <input type="text" class="form-control" id="handingover" name="handingover" placeholder="@lang('messages.Enter handingover')" value="{{ $stockin->handingover }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="origin">@lang('messages.Origin')</label>
                                <input type="text" class="form-control" id="origin" name="origin" placeholder="@lang('messages.Enter origin')" value="{{ $stockin->origin }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="receptionist">@lang('messages.receptionist')</label>
                                    <input type="text" class="form-control" id="receptionist" name="receptionist" placeholder="@lang('messages.Enter receptionist')" value="{{ $stockin->receptionist }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_movement_type">@lang('messages.Type Entrée')</label>
                                    <select class="form-control" name="item_movement_type" id="item_movement_type" required>
                                    <option disabled="disabled">@lang('messages.Merci de choisir')</option>
                                        <option value="EN" {{ $stockin->item_movement_type == 'EN' ? 'selected' : '' }}>@lang('messages.Entrée Normale')</option>
                                        <option value="ER" {{ $stockin->item_movement_type == 'ER' ? 'selected' : '' }}>@lang('messages.Entrée Retour Marchandises')</option>
                                        @if (Auth::guard('admin')->user()->can('raw_material_stockin.edit'))
                                        <option value="EAJ" {{ $stockin->item_movement_type == 'EAJ' ? 'selected' : '' }}>@lang('messages.Entrée Ajustement')</option>
                                        @endif
                                        <option value="EAU" {{ $stockin->item_movement_type == 'EAU' ? 'selected' : '' }}>@lang('messages.Entrée Autre')</option>
                                </select>
                                </div>
                            </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="store_type">@lang('messages.Store Type')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="store_type" id="store_type" required>
                                        <option disabled="disabled">@lang('messages.Merci de choisir')</option>
                                        @if (Auth::guard('admin')->user()->can('raw_material_extra_big_inventory.view'))
                                        <option value="0" {{ $stockin->store_type == '0' ? 'selected' : '' }}>Food Extra Big Store</option>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_big_inventory.view'))
                                        <option value="1" {{ $stockin->store_type == '1' ? 'selected' : '' }}>@lang('messages.Stock intermediaire des matieres premieres')</option>
                                        @endif
                                        @if (Auth::guard('admin')->user()->can('raw_material_small_inventory.view'))
                                        <option value="2" {{ $stockin->store_type == '2' ? 'selected' : '' }}>Petit Stock des matières premières</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="dynamic_store">
                                
                            </div>

                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.purchase_price')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            @foreach($stockinDetails as $index => $detail)
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        @php
                                            $raw_material = $detail->rawMaterial;
                                            if($raw_material && $raw_material->rawMaterialMeasurement) {
                                                $raw_materialName = $raw_material->name . '/' . number_format($raw_material->cump ?? 0,0,',',' ') . '/' . $raw_material->rawMaterialMeasurement->purchase_unit;
                                            } else {
                                                $raw_materialName = '';
                                            }
                                        @endphp
                                        <input type="text" class="form-control food_search" placeholder="@lang('messages.Tapez pour rechercher un article...')" autocomplete="off" value="{{ $raw_materialName }}" required>
                                        <input type="hidden" name="r_material_id[]" class="r_material_id" value="{{ $detail->r_material_id }}">
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" placeholder="@lang('messages.Enter quantity')" class="form-control" value="{{ $detail->quantity }}" step="any" min="0" /></td>  
                                <td><input type="number" name="purchase_price[]" placeholder="@lang('messages.Enter purchase price')" class="form-control" value="{{ $detail->purchase_price }}" step="any" min="0" /></td>     
                                <td>
                                    @if($index === 0)
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
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Entrer description')">{{ $stockin->description }}</textarea>
                        </div>
                        <div style="margin-top: 15px;margin-left: 15px;">
                            <button type="submit" onclick="this.style.visibility='hidden';" ondblclick="this.style.visibility='hidden';" class="btn btn-primary">@lang('messages.update')</button>
                            <a href="{{ route('admin.raw-material-stockins.index') }}" class="btn btn-secondary">@lang('messages.cancel')</a>
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
            minChars: 1,
            onSelect: function(item) {
                $wrap.closest('tr').find('input[name="purchase_price[]"]').val(item.cump ?? 0);
            }
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
                          "<input type='number' name='quantity[]' step='any' placeholder='" + i18n.quantity + "' min='0' class='form-control' />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' step='any' name='purchase_price[]' placeholder='" + i18n.purchasePrice + "' class='form-control' min='0' />"+
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
        // Clear previous store selection
        $("#dynamic_store").empty();
        
        if ($(this).val() === '0'){
            var extra_big_store = "<div class='form-group'>"+
                                "<label for='dest_extra_store_id'>@lang('messages.Food Extra Big Store')<strong style='color: red;'>*</strong></label>"+
                                "<select name='dest_extra_store_id' id='dest_extra_store_id' class='form-control' required>"+
                                    "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                    "@foreach($raw_material_extra_big_stores as $raw_material_big_store)"+
                                    "<option value='{{$raw_material_big_store->id}}' {{ $stockin->dest_extra_store_id == $raw_material_big_store->id ? 'selected' : '' }}>{{ $raw_material_big_store->code}}&nbsp;{{ $raw_material_big_store->name}}</option>"+
                                    "@endforeach"+
                                "</select>"+
                            "</div>";
            
            $("#dynamic_store").append(extra_big_store);
        }
        if ($(this).val() === '1'){
            var big_store = "<div class='form-group'>"+
                                "<label for='dest_bg_store_id'>@lang('messages.Stock intermediaire des matieres premieres')<strong style='color: red;'>*</strong></label>"+
                                "<select name='dest_bg_store_id' id='dest_bg_store_id' class='form-control' required>"+
                                    "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                    "@foreach($raw_material_big_stores as $raw_material_big_store)"+
                                    "<option value='{{$raw_material_big_store->id}}' {{ $stockin->dest_bg_store_id == $raw_material_big_store->id ? 'selected' : '' }}>{{ $raw_material_big_store->code}}&nbsp;{{ $raw_material_big_store->name}}</option>"+
                                    "@endforeach"+
                                "</select>"+
                            "</div>";
            
            $("#dynamic_store").append(big_store);
        }
        if ($(this).val() === '2'){
            var small_store = "<div class='form-group'>"+
                                "<label for='dest_sm_store_id'>@lang('messages.Petit Stock des matières premières')<strong style='color: red;'>*</strong></label>"+
                                "<select name='dest_sm_store_id' id='dest_sm_store_id' class='form-control' required>"+
                                    "<option selected disabled>@lang('messages.merci de choisir')</option>"+
                                    "@foreach($raw_material_small_stores as $raw_material_small_store)"+
                                    "<option value='{{$raw_material_small_store->id}}' {{ $stockin->dest_sm_store_id == $raw_material_small_store->id ? 'selected' : '' }}>{{ $raw_material_small_store->code }}&nbsp;{{ $raw_material_small_store->name}}</option>"+
                                    "@endforeach"+
                                "</select>"+
                            "</div>";
            
            $("#dynamic_store").append(small_store);
        }
    });

    // Trigger change on page load to show the appropriate store dropdown
    $(document).ready(function() {
        $('#store_type').trigger('change');
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

