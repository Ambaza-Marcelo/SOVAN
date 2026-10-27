@extends('backend.layouts.master')

@section('title')
@lang('messages.stockout_create') - @lang('messages.admin_panel')
@endsection

@section('styles')
<style>
    .ajax-ac-wrap {
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
                <h4 class="page-title pull-left">@lang('messages.stockout_create')</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">@lang('messages.dashboard')</a></li>
                    <li><a href="{{ route('admin.finished-goods-stockouts.index') }}">@lang('messages.list')</a></li>
                    <li><span>@lang('messages.stockout_create')</span></li>
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
                    <h4 class="header-title">@lang('messages.Nouvelle Sortie Produits Finis')</h4>
                    @include('backend.layouts.partials.messages')
                    
                    <form action="{{ route('admin.finished-goods-stockouts.store') }}" method="POST">
                        @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                             <label for="date">@lang('messages.date')</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="asker">@lang('messages.Demandeur')</label>
                                <input type="text" class="form-control" id="asker" name="asker" placeholder="@lang('messages.Enter asker')" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="destination">@lang('messages.Destination')</label>
                                <input type="text" class="form-control" id="destination" name="destination" placeholder="@lang('messages.Enter destination')" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="requisition_no">@lang('messages.N° Réquisition')</label>
                                    <input type="text" class="form-control" id="requisition_no" name="requisition_no" placeholder="@lang('messages.Enter requisition number')">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_movement_type">@lang('messages.Type Sortie')</label>
                                    <select class="form-control" name="item_movement_type" id="item_movement_type" required>
                                        <option value="SAU" selected="selected">@lang('messages.SAU - Sortie Autre')</option>
                                        <option value="SP">@lang('messages.SP - Sortie Perte')</option>
                                        <option value="SV">@lang('messages.SV - Sortie Vol')</option>
                                        <option value="SC">@lang('messages.SC - Sortie Casse')</option>
                                        @if (Auth::guard('admin')->user()->can('finished_goods_stockout.edit'))
                                        <option value="SAJ">@lang('messages.SAJ - Sortie Ajustement')</option>
                                        @endif
                                </select>
                                </div>
                            </div>
                    </div>
                    <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="store_type">@lang('messages.Type de Stock Produits Finis')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="store_type" id="store_type" required>
                                        <option value="MAIN" selected>@lang('messages.Stock Principal')</option>
                                        <option value="SM">@lang('messages.Stock SM')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="finished_g_store_id">@lang('messages.Magasin d\'Origine')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="finished_g_store_id" id="finished_g_store_id" required>
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($finished_goods_stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->code }} - {{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                    </div>
                    <div class="row" id="sm_store_row" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="finished_g_sm_store_id">@lang('messages.Magasin SM d\'Origine')<strong style="color: red;">*</strong></label>
                                    <select class="form-control" name="finished_g_sm_store_id" id="finished_g_sm_store_id">
                                        <option disabled="disabled" selected="selected">@lang('messages.Merci de choisir')</option>
                                        @foreach($finished_goods_sm_stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->code }} - {{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_purchase_or_sale_currency">@lang('messages.Devise')</label>
                                    <input type="text" class="form-control" id="item_purchase_or_sale_currency" name="item_purchase_or_sale_currency" placeholder="BIF" value="BIF">
                                </div>
                            </div>
                    </div>
                         <table class="table table-bordered" id="dynamicTable">  
                            <tr>
                                <th>@lang('messages.item')</th>
                                <th>@lang('messages.quantity')</th>
                                <th>@lang('messages.Prix de Coût')</th>
                                <th>@lang('messages.Action')</th>
                            </tr>
                            <tr>  
                                <td>
                                    <div class="ajax-ac-wrap">
                                        <input type="text" class="form-control item_search" placeholder="@lang('messages.Tapez pour rechercher un article...')" autocomplete="off" required>
                                        <input type="hidden" name="item_id[]" class="item_id">
                                    </div>
                                </td>  
                                <td><input type="number" name="quantity[]" placeholder="@lang('messages.Enter quantity')" class="form-control"  step="any" min="0" required/></td>  
                                <td><input type="number" name="cost_price[]" placeholder="@lang('messages.Enter cost price')" class="form-control"  step="any" min="0" readonly/></td>     
                                <td><button type="button" name="add" id="add" class="btn btn-success">@lang('messages.addmore')</button></td>  
                            </tr>  
                        </table> 
                        <div class="col-lg-12">
                            <label for="description"> @lang('messages.description')</label>
                            <textarea class="form-control" name="description" id="description" placeholder="@lang('messages.Entrer description')" required>
                                MOI {{ Auth::guard('admin')->user()->name }},J'ACCUSE LA SORTIE DE CES ARTICLES
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
@include('backend.layouts.partials.ajax_autocomplete')
    <script type="text/javascript">
    
    var itemsSearchUrl = {!! json_encode(route('admin.finished-goods-stockouts.items-search')) !!};

    function getSelectedStoreParams() {
        var storeType = $('#store_type').val();
        var storeId = storeType === 'SM' ? $('#finished_g_sm_store_id').val() : $('#finished_g_store_id').val();
        return { storeType: storeType, storeId: storeId };
    }

    function initItemAutocomplete($wrap) {
        attachAjaxAutocomplete({
            $input: $wrap.find('.item_search'),
            $hidden: $wrap.find('.item_id'),
            url: itemsSearchUrl,
            minChars: 1,
            validateBeforeSearch: function() {
                var params = getSelectedStoreParams();
                return params.storeType && params.storeId;
            },
            extraParams: function() {
                var params = getSelectedStoreParams();
                return { store_type: params.storeType, store_id: params.storeId, limit: 20 };
            },
            renderItem: function(item) {
                return '<div><strong>' + (item.text || item.label || '') + '</strong></div>';
            },
            onSelect: function(item) {
                $wrap.closest('tr').find('input[name="cost_price[]"]').val(item.cump ?? 0);
            }
        });
    }

    $(document).ready(function() {
        function syncStoreTypeUI() {
            var type = $('#store_type').val();
            if (type === 'SM') {
                $('#sm_store_row').show();
                $('#finished_g_sm_store_id').prop('required', true);
                $('#finished_g_store_id').prop('required', false);
            } else {
                $('#sm_store_row').hide();
                $('#finished_g_sm_store_id').prop('required', false);
                $('#finished_g_store_id').prop('required', true);
            }
        }

        $('#store_type').on('change', syncStoreTypeUI);
        syncStoreTypeUI();

        $('#finished_g_store_id, #finished_g_sm_store_id, #store_type').on('change', function () {
            $('#dynamicTable .item_id').val('');
            $('#dynamicTable .item_search').val('');
            $('#dynamicTable input[name="cost_price[]"]').val('');
        });

        $('.ajax-ac-wrap').each(function() {
            if ($(this).find('.item_search').length) {
                initItemAutocomplete($(this));
            }
        });
    });
    
    var i = 0;
       
    $("#add").click(function(){
        ++i;
         var markup = "<tr>"+
                      "<td>"+
                         "<div class='ajax-ac-wrap'>"+
                            "<input type='text' class='form-control item_search' placeholder='" + i18n.searchItem + "' autocomplete='off' required>"+
                            "<input type='hidden' name='item_id[]' class='item_id'>"+
                         "</div>"+
                        "</td>"+
                        "<td>"+
                          "<input type='number' name='quantity[]' step='any' placeholder='" + i18n.quantity + "' min='0' class='form-control' required />"+
                        "</td>"+
                        "<td>"+
                        "<input type='number' step='any' name='cost_price[]' placeholder='" + i18n.costPrice + "' class='form-control' min='0' readonly />"+
                        "</td>"+
                        "<td>"+
                          "<button type='button' class='btn btn-danger remove-tr'>@lang('messages.delete')</button>"+
                        "</td>"+
                    "</tr>";
        var newRow = $(markup);
        $("#dynamicTable").append(newRow);
        initItemAutocomplete(newRow.find('.ajax-ac-wrap'));
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

